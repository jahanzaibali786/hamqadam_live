<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Profile;

use App\Models\Member;
use App\Models\PackageUsage;
use App\Models\ProfileViewer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProfileViewService
{
    public function sent(User $viewer, int $perPage = 20): LengthAwarePaginator
    {
        return ProfileViewer::with([
            'user.member',
            'user.addresses',
            'user.education',
            'user.career',
            'user.physical_attributes',
            'user.spiritual_backgrounds',
            'user.profile_match_for_viewer' => fn ($query) => $query->where('user_id', $viewer->id),
        ])
            ->where('viewed_by', $viewer->id)
            ->latest()
            ->paginate($perPage);
    }

    public function received(User $viewer, int $perPage = 20): LengthAwarePaginator
    {
        return ProfileViewer::with([
            'profileViewer.member',
            'profileViewer.addresses',
            'profileViewer.education',
            'profileViewer.career',
            'profileViewer.physical_attributes',
            'profileViewer.spiritual_backgrounds',
            'profileViewer.profile_match_for_viewer' => fn ($query) => $query->where('user_id', $viewer->id),
        ])
            ->where('user_id', $viewer->id)
            ->latest()
            ->paginate($perPage);
    }

    public function balance(User $viewer): array
    {
        $member = $viewer->member;
        $package = $member?->package;

        return [
            'remaining_profile_viewer_view' => (int) ($member?->remaining_profile_viewer_view ?? 0),
            'used_profile_views' => max(0, (int) ($package?->profile_viewers_view ?? 0) - (int) ($member?->remaining_profile_viewer_view ?? 0)),
            'package_validity' => $member?->package_validity ? Carbon::parse($member->package_validity)->toDateString() : null,
            'is_active' => (bool) ($member && $member->current_package_id && package_validity($viewer->id)),
            'current_package_id' => $member?->current_package_id,
            'package' => $package,
        ];
    }

    public function view(User $viewer, int $profileId): array
    {
        $profile = User::with([
            'member',
            'addresses',
            'education',
            'career',
            'physical_attributes',
            'spiritual_backgrounds',
            'profile_match_for_viewer' => fn ($query) => $query->where('user_id', $viewer->id),
        ])
            ->where('user_type', 'member')
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereKey($profileId)
            ->firstOrFail();

        $profileViewer = ProfileViewer::where('user_id', $profile->id)
            ->where('viewed_by', $viewer->id)
            ->first();

        $alreadyViewed = (bool) $profileViewer;
        $consumed = false;

        if (! $alreadyViewed) {
            DB::transaction(function () use (&$profileViewer, $profile, $viewer): void {
                $member = Member::where('user_id', $viewer->id)->lockForUpdate()->first();

                if (! $member || ! package_validity($viewer->id) || (int) $member->remaining_profile_viewer_view <= 0) {
                    return;
                }

                $profileViewer = ProfileViewer::create([
                    'user_id' => $profile->id,
                    'viewed_by' => $viewer->id,
                ]);

                $member->remaining_profile_viewer_view = max(0, (int) $member->remaining_profile_viewer_view - 1);
                $member->save();

                PackageUsage::record(
                    $viewer->id,
                    'profile_viewer_view',
                    'Profile Viewer View',
                    1,
                    ProfileViewer::class,
                    $profileViewer->id,
                    'Used 1 profile view coin.'
                );
            });

            $consumed = (bool) $profileViewer;
        }

        $freshMember = Member::where('user_id', $viewer->id)->first();

        return [
            'profile' => $profile,
            'consumed' => $consumed,
            'already_viewed' => $alreadyViewed,
            'remaining_profile_viewer_view' => (int) ($freshMember?->remaining_profile_viewer_view ?? 0),
            'package_validity' => $freshMember?->package_validity ? Carbon::parse($freshMember->package_validity)->toDateString() : null,
            'is_active' => (bool) ($freshMember && $freshMember->current_package_id && package_validity($viewer->id)),
        ];
    }
}
