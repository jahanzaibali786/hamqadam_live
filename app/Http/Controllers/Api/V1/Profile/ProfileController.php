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
use App\Services\Api\V1\Profile\ProfileService;
use App\Services\Api\V1\Profile\ProfileViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly CompatibilityScoringService $compatibility,
        private readonly ProfileViewService $profileViews,
    ) {
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

        $stored = ProfileMatch::where('user_id', $request->user()->id)
            ->where('match_id', $candidate->id)
            ->first();

        $score = $stored ? [
            'percentage' => (int) $stored->match_percentage,
            'breakdown' => $stored->score_breakdown ?: [],
            'reasons' => $stored->compatibility_reasons ?: [],
            'explanation' => $stored->compatibility_explanation,
            'calculated_at' => optional($stored->calculated_at)->toISOString(),
            'source' => 'stored',
        ] : $this->compatibility->score($request->user(), $candidate) + [
            'calculated_at' => now()->toISOString(),
            'source' => 'live_rule_based',
        ];

        return $this->success([
            'profile_id' => $candidate->id,
            'compatibility_percentage' => $score['percentage'],
            'compatibility_explanation' => $score['explanation'],
            'compatibility_reasons' => $score['reasons'],
            'score_breakdown' => $score['breakdown'],
            'calculated_at' => $score['calculated_at'],
            'source' => $score['source'],
        ], 'Compatibility fetched successfully.');
    }
}
