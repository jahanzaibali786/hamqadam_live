<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Guest access to the discover feed — the "Proposals for you" preview screen
 * shown to visitors who have not signed up yet.
 *
 * Same visibility rules as the signed-in search (blocked / deactivated /
 * hidden-profile / invisible-mode members excluded, approved-only where the
 * flag is on) but WITHOUT anything viewer-specific: no partner-preference
 * scoping, no ignore lists, no AI re-scoring, no search-history row. A guest
 * sees the shared public pool, newest first.
 *
 * Deliberately minimal response (name, age, city, job title, photo,
 * introduction, verified flag) so nothing leaks that a member chose to keep
 * behind login — the preview is marketing, not a free window into the full
 * database.
 */
class PublicDiscoverController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(12, max(3, (int) $request->integer('per_page', 6)));
        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->with(['member', 'addresses.city', 'spiritual_backgrounds'])
            ->where('user_type', 'member')
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->when((bool) get_setting('search_only_approved'), fn ($q) => $q->where('approved', 1))
            ->whereHas('member', fn ($q) => $q->where('hide_profile', 0))
            ->whereDoesntHave('profile_privacy_setting', fn ($q) => $q->where('invisible_mode', true));

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("CONCAT(TRIM(COALESCE(first_name, '')), ' ', TRIM(COALESCE(last_name, ''))) LIKE ?", [$like])
                    ->orWhere('code', 'like', $like);
            });
        }

        $users = $query
            ->orderByDesc('id')
            ->limit($perPage)
            ->get();

        $profiles = $users->map(function (User $u) {
            $city = $u->addresses->first()?->city?->name;

            return [
                'id' => $u->id,
                'code' => $u->code,
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                // Same photo gate the signed-in surfaces use: a guest never
                // sees a picture the member kept behind the privacy setting or
                // that a moderator has not approved yet — avatar placeholder
                // instead (MaleResource parity via show_profile_picture()).
                'photo' => show_profile_picture($u)
                    ? uploaded_asset($u->photo)
                    : static_asset($u->member?->gender == 1
                        ? 'assets/img/avatar-place.png'
                        : 'assets/img/female-avatar-place.png'),
                'age' => $u->member?->birthday ? \Carbon\Carbon::parse($u->member->birthday)->age : null,
                'gender' => $u->member?->gender,
                'city' => $city,
                'profession' => $u->member?->job_title,
                'introduction' => $u->member?->introduction
                    ? \Illuminate\Support\Str::limit(strip_tags((string) $u->member->introduction), 140)
                    : null,
                'verified' => $u->member?->verification_status === 'verified'
                    || $u->member?->ai_verification_status === 'approved',
            ];
        })->values();

        return $this->success([
            'profiles' => $profiles,
            'total_members' => (int) User::where('user_type', 'member')
                ->where('blocked', 0)
                ->where('deactivated', 0)
                ->count(),
        ]);
    }
}
