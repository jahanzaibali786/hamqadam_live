<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Search\ProfileSearchRequest;
use App\Http\Requests\Api\V1\Search\StoreSavedSearchRequest;
use App\Http\Resources\Api\V1\Search\SavedSearchResource;
use App\Http\Resources\Api\V1\Search\SearchHistoryResource;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Services\Api\V1\Matching\MatchmakingIntegrationService;
use App\Services\Api\V1\Search\ProfileSearchService;
use App\Services\Api\V1\Search\SavedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __construct(
        private readonly ProfileSearchService $profiles,
        private readonly SavedSearchService $savedSearches,
        private readonly MatchmakingIntegrationService $matchmaking,
    ) {
    }

    public function profiles(ProfileSearchRequest $request): JsonResponse
    {
        $page = $this->profiles->search($request->user(), $request->validated());

        $this->attachAiScores($request->user(), $page);

        return SearchProfileResource::collection($page)
            ->additional(['success' => true])->response();
    }

    /**
     * Fills in a compatibility score for listed profiles that have no stored one.
     *
     * Without this every card on a fresh account showed 0%: the resource reads
     * `profile_match_for_viewer`, and a member who has never had a scoring run
     * simply has no such row, so the whole discover feed was a wall of zeroes.
     *
     * The whole page is scored in ONE call - the model takes the candidate list
     * with the request - so this costs a single round trip per search, not one
     * per card. A stored row still wins where it exists, and if the model is
     * unreachable the method returns quietly and the listing behaves exactly as
     * it did before.
     */
    private function attachAiScores($viewer, $page): void
    {
        if (! $viewer) {
            return;
        }

        $needScore = collect($page->items())
            ->filter(fn ($user) => $user->profile_match_for_viewer?->match_percentage === null)
            ->values();

        if ($needScore->isEmpty()) {
            return;
        }

        $result = $this->matchmaking->getMatches($viewer, $needScore->all());
        if ($result === null || empty($result['matches'])) {
            return;
        }

        $byCandidate = collect($result['matches'])
            ->keyBy(fn (array $m) => (string) ($m['candidate_id'] ?? ''));

        foreach ($needScore as $user) {
            $match = $byCandidate->get((string) $user->id);
            if ($match !== null && isset($match['match_score'])) {
                $user->setAttribute('ai_match_percentage', (int) $match['match_score']);
            }
        }
    }

    public function saved(Request $request): JsonResponse
    {
        return SavedSearchResource::collection($this->savedSearches->list($request->user()))
            ->additional(['success' => true])
            ->response();
    }

    public function storeSaved(StoreSavedSearchRequest $request): JsonResponse
    {
        $savedSearch = $this->savedSearches->store($request->user(), $request->validated());

        return $this->success(new SavedSearchResource($savedSearch), 'Search saved successfully.', 201);
    }

    public function deleteSaved(Request $request, int $id): JsonResponse
    {
        $this->savedSearches->delete($request->user(), $id);

        return $this->success(message: 'Saved search deleted successfully.');
    }

    public function history(Request $request): JsonResponse
    {
        return SearchHistoryResource::collection($this->savedSearches->history($request->user()))
            ->additional(['success' => true])
            ->response();
    }

    public function hideFrom(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $this->savedSearches->hideFrom($request->user(), (int) $data['user_id']);

        return $this->success(message: 'Your profile is now hidden from this user.');
    }

    public function unhideFrom(Request $request, int $user): JsonResponse
    {
        $this->savedSearches->unhideFrom($request->user(), $user);

        return $this->success(message: 'Your profile is visible to this user again.');
    }
}
