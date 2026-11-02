<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Appreciation;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\ProfileLike;
use App\Models\ProfileSuperLike;
use App\Models\User;
use App\Services\ProfileAppreciationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Like (free) and Super Like (coins) for the mobile app.
 *
 * Thin wrapper over ProfileAppreciationService so app and web share one
 * set of rules: eligibility, idempotent charge, notifications. Activity is
 * served from the same ProfileLike / ProfileSuperLike rows, newest first.
 */
class AppreciationController extends ApiController
{
    public function __construct(private readonly ProfileAppreciationService $service)
    {
    }

    public function status(Request $request, int $member): JsonResponse
    {
        $me = (int) $request->user()->id;

        return $this->success([
            'liked' => ProfileLike::where('sender_id', $me)->where('recipient_id', $member)->exists(),
            'super_liked' => ProfileSuperLike::where('sender_id', $me)->where('recipient_id', $member)->exists(),
            'liked_me' => ProfileLike::where('sender_id', $member)->where('recipient_id', $me)->exists(),
            'super_liked_me' => ProfileSuperLike::where('sender_id', $member)->where('recipient_id', $me)->exists(),
            'super_like_cost' => $this->cost(),
            'coin_balance' => (int) optional($request->user()->member)->remaining_interest,
        ]);
    }

    public function like(Request $request, int $member): JsonResponse
    {
        $liked = $this->service->toggleLike($request->user(), $member);
        $mutual = $liked && ProfileLike::where('sender_id', $member)->where('recipient_id', $request->user()->id)->exists();

        return $this->success(['liked' => $liked, 'mutual' => $mutual], $liked ? 'Liked' : 'Like removed');
    }

    public function superLike(Request $request, int $member): JsonResponse
    {
        $data = $request->validate(['quoted_cost' => ['required', 'integer', 'min:0']]);
        $result = $this->service->superLike($request->user(), $member, (int) $data['quoted_cost']);

        return $this->success(['super_liked' => true] + $result, 'Your Super Like has been sent successfully!');
    }

    /** GET /appreciations/activity?direction=received|sent&type=like|super_like */
    public function activity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:received,sent'],
            'type' => ['required', 'in:like,super_like'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $me = (int) $request->user()->id;
        $received = $data['direction'] === 'received';
        $model = $data['type'] === 'like' ? ProfileLike::class : ProfileSuperLike::class;
        $relation = $received ? 'sender' : 'recipient';

        $page = $model::with($relation)
            ->where($received ? 'recipient_id' : 'sender_id', $me)
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 20));

        $items = collect($page->items())->map(function ($row) use ($relation, $data) {
            /** @var User|null $person */
            $person = $row->{$relation};

            return [
                'id' => $row->id,
                'type' => $data['type'],
                'status' => 'sent',
                'coins_charged' => (int) ($row->coins_charged ?? 0),
                'created_at' => optional($row->created_at)->toIso8601String(),
                'user' => $person ? [
                    'id' => $person->id,
                    'code' => $person->code,
                    'name' => trim($person->first_name.' '.$person->last_name),
                    'photo' => $person->photo ? uploaded_asset($person->photo) : null,
                ] : null,
            ];
        })->values();

        return $this->success($items, null, 200, [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]);
    }

    private function cost(): int
    {
        return max(0, (int) get_setting('feature_coin_cost_super_like', 10));
    }
}
