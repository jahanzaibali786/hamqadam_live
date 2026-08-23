<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Interest;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Interest\InterestResource;
use App\Models\ExpressInterest;
use App\Models\User;
use App\Services\InterestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Express-interest ("proposal") endpoints for the v1 API.
 *
 * The v1 API had none of these; only the website and the legacy /api routes
 * did. This controller reuses InterestService so the coin cost, package-usage
 * logging and notifications behave identically to the web flow.
 *
 * Coins: sending an interest costs feature_coin_cost('express_interest') from
 * the member's remaining_interest balance. Accepting, rejecting and withdrawing
 * are free.
 */
class InterestController extends ApiController
{
    public function __construct(private readonly InterestService $interests)
    {
    }

    /** GET /interests/sent */
    public function sent(Request $request): JsonResponse
    {
        $interests = ExpressInterest::with(['user.member'])
            ->where('interested_by', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $this->statusValue($request->string('status')->toString())))
            ->latest('id')
            ->paginate(min((int) $request->integer('per_page', 15), 50));

        return $this->success([
            'interests' => InterestResource::collection($interests)->resolve($request),
            'meta' => $this->pagination($interests),
            'coin_balance' => $this->balance($request->user()),
        ]);
    }

    /** GET /interests/received */
    public function received(Request $request): JsonResponse
    {
        $interests = ExpressInterest::with(['sender.member'])
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $this->statusValue($request->string('status')->toString())))
            ->latest('id')
            ->paginate(min((int) $request->integer('per_page', 15), 50));

        return $this->success([
            'interests' => InterestResource::collection($interests)->resolve($request),
            'meta' => $this->pagination($interests),
            'pending_count' => ExpressInterest::where('user_id', $request->user()->id)
                ->where('status', ProposalStatus::Pending->value)->count(),
        ]);
    }

    /**
     * POST /interests
     *
     * Costs coins. Returns 402 when the balance is short so the app can send
     * the member to the packages screen instead of showing a generic failure.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'initial_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $sender = $request->user();

        if ((int) $data['user_id'] === (int) $sender->id) {
            return $this->error('You cannot express interest in yourself.', 422, 'self_interest');
        }

        $recipient = User::where('user_type', 'member')
            ->where('approved', 1)->where('blocked', 0)->where('deactivated', 0)
            ->find($data['user_id']);

        if (! $recipient) {
            return $this->error('This member is not available.', 404, 'member_unavailable');
        }

        // An interest already in flight either way blocks a duplicate charge.
        $existing = ExpressInterest::where(function ($q) use ($sender, $recipient) {
            $q->where('interested_by', $sender->id)->where('user_id', $recipient->id);
        })->orWhere(function ($q) use ($sender, $recipient) {
            $q->where('interested_by', $recipient->id)->where('user_id', $sender->id);
        })->whereIn('status', [ProposalStatus::Pending->value, ProposalStatus::Accepted->value])
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->error(
                $existing->status === ProposalStatus::Accepted
                    ? 'You are already connected with this member.'
                    : 'An interest between you and this member is already pending.',
                409,
                'interest_exists'
            );
        }

        $cost = (int) feature_coin_cost('express_interest', 1);
        $balance = (int) ($sender->member?->remaining_interest ?? 0);

        if ($balance < $cost) {
            return $this->error(
                'You need '.$cost.' coin(s) to send an interest and have '.$balance.'. Upgrade your package to continue.',
                402,
                'insufficient_coins'
            );
        }

        if (! $this->interests->store($recipient->id)) {
            return $this->error('Interest could not be sent. Please try again.', 422, 'interest_failed');
        }

        $interest = ExpressInterest::with('user.member')
            ->where('interested_by', $sender->id)->where('user_id', $recipient->id)
            ->latest('id')->first();

        if ($interest && array_key_exists('initial_note', $data) && $data['initial_note'] !== null) {
            $interest->initial_note = $data['initial_note'];
            $interest->save();
        }

        return $this->success([
            'interest' => $interest ? (new InterestResource($interest))->resolve($request) : null,
            'coins_spent' => $cost,
            'coin_balance' => $this->balance($sender->fresh('member')),
        ], 'Interest sent successfully.', 201);
    }

    /** POST /interests/{interest}/accept */
    public function accept(Request $request, int $interest): JsonResponse
    {
        $model = $this->pendingForRecipient($request, $interest);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        if (! $this->interests->accept($model->id)) {
            return $this->error('Interest could not be accepted.', 422, 'accept_failed');
        }

        return $this->success([
            'interest' => (new InterestResource($model->fresh(['sender.member'])))->resolve($request),
        ], 'Interest accepted. You can now chat with this member.');
    }

    /** POST /interests/{interest}/reject */
    public function reject(Request $request, int $interest): JsonResponse
    {
        $model = $this->pendingForRecipient($request, $interest);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        if (! $this->interests->reject($model->id)) {
            return $this->error('Interest could not be rejected.', 422, 'reject_failed');
        }

        return $this->success([
            'interest' => (new InterestResource($model->fresh(['sender.member'])))->resolve($request),
        ], 'Interest rejected.');
    }

    /**
     * DELETE /interests/{interest} — the sender withdraws.
     * Coins are NOT refunded; the recipient was already notified.
     */
    public function withdraw(Request $request, int $interest): JsonResponse
    {
        $model = ExpressInterest::where('interested_by', $request->user()->id)->find($interest);

        if (! $model) {
            return $this->error('Interest not found.', 404, 'not_found');
        }

        if ($model->status !== ProposalStatus::Pending) {
            return $this->error('Only a pending interest can be withdrawn.', 422, 'not_pending');
        }

        $model->status = ProposalStatus::Withdrawn;
        $model->withdrawn_at = now();
        $model->save();

        return $this->success([
            'interest' => (new InterestResource($model->fresh(['user.member'])))->resolve($request),
        ], 'Interest withdrawn.');
    }

    /** GET /interests/coin-balance */
    public function coinBalance(Request $request): JsonResponse
    {
        return $this->success($this->balance($request->user()));
    }

    // ------------------------------------------------------------- helpers

    private function pendingForRecipient(Request $request, int $id): ExpressInterest|JsonResponse
    {
        $model = ExpressInterest::with('sender.member')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $model) {
            return $this->error('Interest not found.', 404, 'not_found');
        }

        if ($model->status !== ProposalStatus::Pending) {
            return $this->error('This interest has already been answered.', 422, 'already_answered');
        }

        return $model;
    }

    private function balance(User $user): array
    {
        $cost = (int) feature_coin_cost('express_interest', 1);
        $remaining = (int) ($user->member?->remaining_interest ?? 0);

        return [
            'remaining_interest' => $remaining,
            'cost_per_interest' => $cost,
            'can_send' => $remaining >= $cost,
        ];
    }

    private function statusValue(string $status): int
    {
        return match ($status) {
            'pending' => ProposalStatus::Pending->value,
            'accepted' => ProposalStatus::Accepted->value,
            'rejected' => ProposalStatus::Rejected->value,
            'withdrawn' => ProposalStatus::Withdrawn->value,
            'cancelled' => ProposalStatus::Cancelled->value,
            'expired' => ProposalStatus::Expired->value,
            default => -1,
        };
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
