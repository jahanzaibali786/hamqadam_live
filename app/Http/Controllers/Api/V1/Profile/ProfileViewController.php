<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Payment\PlanResource;
use App\Http\Resources\Api\V1\Profile\ProfileViewResource;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Services\Api\V1\Profile\ProfileViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileViewController extends ApiController
{
    public function __construct(
        private readonly ProfileViewService $profileViews,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->profileViews->sent($request->user(), $request->integer('per_page', 20));
        $balance = $this->profileViews->balance($request->user());

        return ProfileViewResource::collection($items)->additional([
            'success' => true,
            'summary' => [
                'remaining_profile_viewer_view' => (int) $balance['remaining_profile_viewer_view'],
                'used_profile_views' => (int) $balance['used_profile_views'],
                'package_validity' => $balance['package_validity'],
                'is_active' => (bool) $balance['is_active'],
                'current_package_id' => $balance['current_package_id'],
            ],
        ])->response();
    }

    public function received(Request $request): JsonResponse
    {
        $items = $this->profileViews->received($request->user(), $request->integer('per_page', 20));
        $balance = $this->profileViews->balance($request->user());

        return ProfileViewResource::collection($items)->additional([
            'success' => true,
            'summary' => [
                'remaining_profile_viewer_view' => (int) $balance['remaining_profile_viewer_view'],
                'used_profile_views' => (int) $balance['used_profile_views'],
                'package_validity' => $balance['package_validity'],
                'is_active' => (bool) $balance['is_active'],
                'current_package_id' => $balance['current_package_id'],
            ],
        ])->response();
    }

    public function balance(Request $request): JsonResponse
    {
        $balance = $this->profileViews->balance($request->user());

        return $this->success([
            'current_package' => $balance['package'] ? new PlanResource($balance['package']) : null,
            'package_validity' => $balance['package_validity'],
            'is_active' => $balance['is_active'],
            'remaining_profile_viewer_view' => $balance['remaining_profile_viewer_view'],
            'used_profile_views' => $balance['used_profile_views'],
        ], 'Profile view balance fetched successfully.');
    }

    public function view(Request $request, int $profile): JsonResponse
    {
        $result = $this->profileViews->view($request->user(), $profile);

        if (! $result['allowed']) {
            return $this->error(
                $result['reason'] === 'package_required'
                    ? 'Please purchase an active package before viewing profiles.'
                    : 'Your package has no remaining profile views. Please upgrade your package to continue.',
                422,
                $result['reason'],
                [],
                [
                    'profile_view' => [
                        'consumed' => false,
                        'already_viewed' => false,
                        'remaining_profile_viewer_view' => $result['remaining_profile_viewer_view'],
                        'package_validity' => $result['package_validity'],
                        'is_active' => $result['is_active'],
                        'current_package_id' => $result['current_package_id'],
                        'upgrade_required' => true,
                    ],
                ]
            );
        }

        return $this->success([
            'profile' => new SearchProfileResource($result['profile']),
            'profile_view' => [
                'consumed' => $result['consumed'],
                'already_viewed' => $result['already_viewed'],
                'remaining_profile_viewer_view' => $result['remaining_profile_viewer_view'],
                'package_validity' => $result['package_validity'],
                'is_active' => $result['is_active'],
                'current_package_id' => $result['current_package_id'],
            ],
        ], 'Profile fetched successfully.');
    }
}
