<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Matching;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Matching\StoreMatchFeedbackRequest;
use App\Http\Resources\Api\V1\Matching\MatchResource;
use App\Models\ProfileMatch;
use App\Jobs\RecalculateCompatibilityMatches;
use App\Services\Api\V1\Matching\MatchRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends ApiController
{
    public function __construct(private readonly MatchRecommendationService $matches)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        return MatchResource::collection($this->matches->recommendations($request->user(), $perPage))
            ->additional(['success' => true])
            ->response();
    }

    public function recommended(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function daily(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function show(Request $request, int $profile): JsonResponse
    {
        $match = ProfileMatch::with(['matchedUser.member', 'matchedUser.physical_attributes', 'matchedUser.spiritual_backgrounds'])
            ->where('user_id', $request->user()->id)
            ->where('match_id', $profile)
            ->first();

        if (! $match) {
            $this->matches->recalculateFor($request->user()->loadMissing('member', 'partner_expectations'), 250);
            $match = ProfileMatch::with(['matchedUser.member', 'matchedUser.physical_attributes', 'matchedUser.spiritual_backgrounds'])
                ->where('user_id', $request->user()->id)
                ->where('match_id', $profile)
                ->firstOrFail();
        }

        return $this->success(new MatchResource($match));
    }

    public function recalculate(Request $request): JsonResponse
    {
        $limit = min((int) $request->integer('limit', 100), 250);
        $count = $this->matches->recalculateFor($request->user()->loadMissing('member', 'partner_expectations'), $limit);

        return $this->success([
            'processed_profiles' => $count,
        ], 'Compatibility matches recalculated successfully.');
    }

    public function recalculateAsync(Request $request): JsonResponse
    {
        $limit = min((int) $request->integer('limit', 100), 250);
        RecalculateCompatibilityMatches::dispatch($request->user()->id, $limit);

        return $this->success([
            'queued' => true,
        ], 'Compatibility recalculation has been queued.', 202);
    }

    public function feedback(StoreMatchFeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();
        $feedback = $this->matches->storeFeedback(
            $request->user(),
            (int) $data['user_id'],
            $data['feedback'],
            $data['source'] ?? null,
            $data['note'] ?? null
        );

        return $this->success([
            'id' => $feedback->id,
            'feedback' => $feedback->feedback,
        ], 'Match feedback saved successfully.', 201);
    }
}
