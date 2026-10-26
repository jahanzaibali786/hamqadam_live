<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Interest;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Interest\InterestResource;
use App\Models\ExpressInterest;
use App\Models\PackageUsage;
use App\Models\AnalyticsEvent;
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

    /**
     * The relations the card needs for BOTH sides: the resource resolves the
     * counterpart (sender on "received", recipient on "sent") and renders
     * name, age, city, education, profession, income and the viewer-relative
     * match score. Loading them once per page keeps the list at a handful of
     * queries instead of five per row.
     */
    private const CARD_RELATIONS = [
        'sender.member',
        'user.member',
        'sender.education.educationLevel',
        'sender.career.profession',
        'sender.addresses.city',
        'sender.profile_match_for_viewer',
        'user.education.educationLevel',
        'user.career.profession',
        'user.addresses.city',
        'user.profile_match_for_viewer',
    ];

    /** GET /interests/sent */
    public function sent(Request $request): JsonResponse
    {
        $interests = ExpressInterest::with(self::CARD_RELATIONS)
            ->where('interested_by', $request->user()->id)
            // Members an admin deleted drop out of the list instead of leaving a
            // card that opens to a 404.
            ->whereHas('user', fn ($q) => $q->withoutTrashed())
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
        $interests = ExpressInterest::with(self::CARD_RELATIONS)
            ->where('user_id', $request->user()->id)
            ->whereHas('sender', fn ($q) => $q->withoutTrashed())
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
        return $this->error(
            'Select and approve an active interest template using /api/v1/connection-workflow/members/{member}/interest.',
            409,
            'interest_template_approval_required'
        );
    }

    /** POST /interests/{interest}/accept */
    public function accept(Request $request, int $interest): JsonResponse
    {
        $model = $this->pendingForRecipient($request, $interest);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        if (!app(\App\Services\MemberConnectionWorkflow::class)->respondInterest($request->user(),$model,true)) {
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

        if (!app(\App\Services\MemberConnectionWorkflow::class)->respondInterest($request->user(),$model,false)) {
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
            ->where('interaction_type','interest')
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
        $cost = (int) feature_coin_cost('express_interest', 3);
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

    /**
     * True when [flags] grants [name].
     *
     * Handles both stored shapes: `['priority_interest' => true]` (the JSON
     * object the admin package form saves) and `['priority_interest']` (a plain
     * list). Only the former was ever written, which is why `in_array()` never
     * matched.
     */
    private function hasFeatureFlag(array $flags, string $name): bool
    {
        if (array_key_exists($name, $flags)) {
            return (bool) $flags[$name];
        }

        return in_array($name, $flags, true);
    }
}
