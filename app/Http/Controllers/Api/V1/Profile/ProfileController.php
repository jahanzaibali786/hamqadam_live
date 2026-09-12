<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Profile\UpdatePrivacyRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UpdateVisibilityRequest;
use App\Http\Resources\Api\V1\Profile\ProfilePrivacyResource;
use App\Http\Resources\Api\V1\Profile\ProfileResource;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Models\ProfileMatch;
use App\Models\User;
use App\Services\Api\V1\Matching\CompatibilityScoringService;
use App\Services\Api\V1\Matching\MatchmakingIntegrationService;
use App\Services\Api\V1\Profile\ProfileService;
use App\Services\Api\V1\Profile\ProfileViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{    public function __construct(
        private readonly ProfileService $profiles,
        private readonly CompatibilityScoringService $compatibility,
        private readonly MatchmakingIntegrationService $sidecar,
        private readonly ProfileViewService $profileViews,
    )
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->success(new ProfileResource($this->profiles->getProfile($request->user())));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->updateProfile($request->user(), $request->validated());

        return $this->success(new ProfileResource($profile), 'Profile updated successfully.');
    }

    public function updatePrivacy(UpdatePrivacyRequest $request): JsonResponse
    {
        $privacy = $this->profiles->updatePrivacy($request->user(), $request->validated());

        return $this->success(new ProfilePrivacyResource($privacy), 'Privacy settings updated successfully.');
    }

    public function updateVisibility(UpdateVisibilityRequest $request): JsonResponse
    {
        $profile = $this->profiles->updateVisibility($request->user(), (bool) $request->boolean('hide_profile'));

        return $this->success(new ProfileResource($profile), 'Profile visibility updated successfully.');
    }

    public function deactivate(Request $request): JsonResponse
    {
        $this->profiles->deactivate($request->user());

        return $this->success(message: 'Profile deactivated successfully.');
    }

    public function publicProfile(Request $request, int $profile): JsonResponse
    {
        $result = $this->profileViews->view($request->user(), $profile);

        return $this->success(
            new SearchProfileResource($result['profile']),
            'Profile fetched successfully.',
            200,
            [
                'profile_view' => [
                    'consumed' => $result['consumed'],
                    'already_viewed' => $result['already_viewed'],
                    'remaining_profile_viewer_view' => $result['remaining_profile_viewer_view'],
                    'package_validity' => $result['package_validity'],
                    'is_active' => $result['is_active'],
                ],
            ]
        );
    }

    public function compatibility(Request $request, int $profile): JsonResponse
    {
        $viewer = $request->user()->loadMissing('member', 'partner_expectations');
        $candidate = User::with([
            'member',
            'addresses',
            'education',
            'career',
            'physical_attributes',
            'spiritual_backgrounds',
            'lifestyles',
            'partner_expectations',
        ])
            ->where('user_type', 'member')
            ->where('approved', 1)
            ->whereKey($profile)
            ->firstOrFail();

        $stored = ProfileMatch::where('user_id', $viewer->id)
            ->where('match_id', $candidate->id)
            ->first();

        // A stored row is a cache, not a verdict. Treat it as authoritative only
        // when it actually carries reasoning: production has 110 of these rows
        // and 43 sit at 0% with nothing to explain them, which is what an empty
        // cache entry looks like, not a real incompatibility. Returning those
        // shadowed the matchmaking model permanently — the same pair the model
        // scores 81% was being shown to the member as 0%.
        $storedIsInformative = $stored
            && ((int) $stored->match_percentage > 0
                || ! empty($stored->compatibility_explanation)
                || ! empty($stored->score_breakdown));

        if ($stored && $storedIsInformative) {
            return $this->success([
                'profile_id'                 => $candidate->id,
                'compatibility_percentage'   => (int) $stored->match_percentage,
                'compatibility_explanation'  => $stored->compatibility_explanation,
                'compatibility_reasons'      => $stored->compatibility_reasons ?: [],
                'score_breakdown'            => $stored->score_breakdown ?: [],
                'calculated_at'              => optional($stored->calculated_at)->toISOString(),
                'source'                     => 'stored',
                // `model_confidence` is not present on every deployment; a missing
                // attribute reads as null rather than throwing, so guard it
                // explicitly instead of pretending the answer is meaningful.
                'model_version'              => ($stored->getAttribute('model_confidence') ?? null) ? 'ai_sidecar' : null,
            ], 'Compatibility fetched successfully.');
        }

        $sidecarResult = $this->sidecar->compatibilityPreview($viewer, $candidate);

        if ($sidecarResult['source'] === 'sidecar') {
            return $this->success([
                'profile_id'                 => $candidate->id,
                'compatibility_percentage'   => $sidecarResult['percentage'],
                'compatibility_explanation'  => $sidecarResult['explanation'],
                'compatibility_reasons'      => $sidecarResult['reasons'],
                'score_breakdown'            => $sidecarResult['breakdown'],
                'calculated_at'              => $sidecarResult['calculated_at'],
                'source'                     => 'ai_sidecar',
            ], 'Compatibility fetched successfully.');
        }

        $score = $this->compatibility->score($viewer, $candidate) + [
            'calculated_at' => now()->toISOString(),
            'source' => 'rule_based_integrated',
        ];

        return $this->success([
            'profile_id'                 => $candidate->id,
            'compatibility_percentage'   => $score['percentage'],
            'compatibility_explanation'  => $score['explanation'],
            'compatibility_reasons'      => $score['reasons'],
            'score_breakdown'            => $score['breakdown'],
            'calculated_at'              => $score['calculated_at'],
            'source'                     => $score['source'],
        ], 'Compatibility fetched successfully.');
    }
}
