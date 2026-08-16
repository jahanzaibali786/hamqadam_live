<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Search\ProfileSearchRequest;
use App\Http\Requests\Api\V1\Search\StoreSavedSearchRequest;
use App\Http\Resources\Api\V1\Search\SavedSearchResource;
use App\Http\Resources\Api\V1\Search\SearchHistoryResource;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Services\Api\V1\Search\ProfileSearchService;
use App\Services\Api\V1\Search\SavedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __construct(
        private readonly ProfileSearchService $profiles,
        private readonly SavedSearchService $savedSearches,
    ) {
    }

    public function profiles(ProfileSearchRequest $request): JsonResponse
    {
        return SearchProfileResource::collection(
            $this->profiles->search($request->user(), $request->validated())
        )->additional(['success' => true])->response();
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
