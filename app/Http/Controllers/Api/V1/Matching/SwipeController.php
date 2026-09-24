<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Matching;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Services\Api\V1\Matching\SwipeDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Swipe Matching.
 *
 * `GET /matches/swipe-deck` is the stack of cards, `POST /matches/swipe`
 * records one decision and answers whether it completed a mutual match,
 * `DELETE /matches/swipe/last` puts the last card back, and
 * `GET /matches/swipe-summary` feeds the counters above the deck.
 */
class SwipeController extends ApiController
{
    public function __construct(private readonly SwipeDeckService $swipes)
    {
    }

    public function deck(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'photo_only' => ['sometimes', 'boolean'],
            'verified_only' => ['sometimes', 'boolean'],
            'online_now' => ['sometimes', 'boolean'],
            'exclude_viewed' => ['sometimes', 'boolean'],
            'age_min' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'age_max' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
        ]);

        $deck = $this->swipes->deck($request->user(), $filters);

        return $this->success([
            'candidates' => SearchProfileResource::collection($deck['candidates'])->resolve($request),
            'meta' => [
                'total' => $deck['total'],
                'remaining' => $deck['remaining'],
                'returned' => $deck['candidates']->count(),
            ],
            'summary' => $this->swipes->summary($request->user()),
        ]);
    }

    public function swipe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:like,pass'],
        ]);

        $result = $this->swipes->swipe($request->user(), (int) $data['user_id'], (string) $data['action']);

        return $this->success([
            'action' => $result['swipe']->action,
            'user_id' => (int) $result['target']->id,
            'is_match' => (bool) $result['is_match'],
            'matched_user' => $result['is_match']
                ? (new SearchProfileResource($result['target']))->resolve($request)
                : null,
            'summary' => $this->swipes->summary($request->user()),
        ], $result['is_match'] ? "It's a match!" : 'Swipe recorded.');
    }

    public function undo(Request $request): JsonResponse
    {
        $target = $this->swipes->undo($request->user());

        return $this->success([
            'restored_user_id' => $target?->id,
            'restored_user' => $target ? (new SearchProfileResource($target))->resolve($request) : null,
            'summary' => $this->swipes->summary($request->user()),
        ], $target ? 'Last swipe undone.' : 'There is nothing to undo.');
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->success($this->swipes->summary($request->user()));
    }
}
