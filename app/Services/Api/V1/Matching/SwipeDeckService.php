<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Matching;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\HiddenProfileUser;
use App\Models\IgnoredUser;
use App\Models\ProfileSwipe;
use App\Models\ProfileViewer;
use App\Models\User;

/**
 * Swipe Matching.
 *
 * The deck is built from the SAME guardrails the Discover search uses —
 * opposite gender by default, blocked / deactivated / hidden / ignored /
 * invisible members out, hidden-from-me members out — so a profile can never
 * appear in the deck while being unfindable everywhere else in the app.
 *
 * Two things are deliberately NOT copied from the search endpoint:
 *
 * * no `search_history` row is written per deck fetch. A deck is browsed, not
 *   searched; logging every swipe-session as a "search" would bury the real
 *   queries in the member's Search History.
 * * a right-swipe does NOT spend coins. Swipes are recorded (and a mutual
 *   like is reported back) but sending a real Express Interest stays the
 *   member's explicit action, because silently charging coins for a gesture
 *   would take money out of their balance on a mis-tap.
 */
class SwipeDeckService
{
    /**
     * Profiles still to swipe: not already swiped by the viewer, newest first,
     * with the AI compatibility score attached where one exists.
     */
    public function deck(User $viewer, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 50));

        $query = $this->candidateQuery($viewer)
            ->with([
                'member',
                'addresses',
                'physical_attributes',
                'spiritual_backgrounds',
                'lifestyles',
                'profile_match_for_viewer' => fn ($match) => $match->where('user_id', $viewer->id),
            ])
            // Already judged once — never again.
            ->whereNotIn('id', ProfileSwipe::where('swiper_user_id', $viewer->id)->pluck('target_user_id'));

        if (! empty($filters['photo_only'])) {
            $query->whereNotNull('photo');
        }

        if (! empty($filters['verified_only'])) {
            $query->where('approved', 1);
        }

        if (! empty($filters['online_now'])) {
            $query->where('last_login_at', '>=', now()->subMinutes((int) (get_setting('search_online_window_minutes') ?: 15)));
        }

        if (! empty($filters['exclude_viewed'])) {
            $query->whereNotIn('id', ProfileViewer::where('viewed_by', $viewer->id)->pluck('user_id'));
        }

        if (! empty($filters['age_max'])) {
            $maxAge = (int) $filters['age_max'];
            $query->whereHas('member', fn ($member) => $member
                ->where('birthday', '>=', now()->subYears($maxAge + 1)->addDay()->toDateString()));
        }

        if (! empty($filters['age_min'])) {
            $minAge = (int) $filters['age_min'];
            $query->whereHas('member', fn ($member) => $member
                ->where('birthday', '<=', now()->subYears($minAge)->toDateString()));
        }

        $total = (clone $query)->count();
        // `users.created_at` is qualified: the eager loads above join member
        // rows that carry their own timestamps.
        $candidates = $query->latest('users.created_at')->limit($perPage)->get();

        return [
            'candidates' => $candidates,
            'remaining' => max(0, $total - $candidates->count()),
            'total' => $total,
        ];
    }

    /**
     * Records a swipe. Returns `is_match` true when the other member had
     * already liked the viewer — the deck's cue to celebrate.
     */
    public function swipe(User $viewer, int $targetId, string $action): array
    {
        $action = $action === ProfileSwipe::LIKE ? ProfileSwipe::LIKE : ProfileSwipe::PASS;
        $target = $this->swipeableMember($viewer, $targetId);

        $swipe = ProfileSwipe::updateOrCreate(
            ['swiper_user_id' => $viewer->id, 'target_user_id' => $target->id],
            ['action' => $action]
        );

        $mutual = false;
        if ($action === ProfileSwipe::LIKE) {
            $mutual = ProfileSwipe::where('swiper_user_id', $target->id)
                ->where('target_user_id', $viewer->id)
                ->where('action', ProfileSwipe::LIKE)
                ->exists();
        }

        return [
            'swipe' => $swipe,
            'target' => $target,
            'is_match' => $mutual,
        ];
    }

    /**
     * Takes back the member's most recent swipe so the card can slide back in.
     * Returns the profile that was un-swiped (null when there was nothing).
     */
    public function undo(User $viewer): ?User
    {
        $swipe = ProfileSwipe::where('swiper_user_id', $viewer->id)
            ->latest('id')
            ->first();

        if (! $swipe) {
            return null;
        }

        $target = User::find($swipe->target_user_id);
        $swipe->delete();

        return $target;
    }

    /** Deck counters for the header ("12 left • 3 likes sent"). */
    public function summary(User $viewer): array
    {
        return [
            'likes_sent' => ProfileSwipe::where('swiper_user_id', $viewer->id)->where('action', ProfileSwipe::LIKE)->count(),
            'passes_sent' => ProfileSwipe::where('swiper_user_id', $viewer->id)->where('action', ProfileSwipe::PASS)->count(),
            'matches' => ProfileSwipe::query()
                ->where('profile_swipes.swiper_user_id', $viewer->id)
                ->where('profile_swipes.action', ProfileSwipe::LIKE)
                ->join('profile_swipes as theirs', function ($join) use ($viewer) {
                    $join->on('theirs.swiper_user_id', '=', 'profile_swipes.target_user_id')
                        ->where('theirs.target_user_id', '=', $viewer->id)
                        ->where('theirs.action', '=', ProfileSwipe::LIKE);
                })
                ->count(),
        ];
    }

    /**
     * The member may only swipe someone who is actually meetable: a live,
     * approved member of the opposite gender scope, not blocked, not hidden
     * from them, not invisible.
     */
    private function swipeableMember(User $viewer, int $targetId): User
    {
        if ($targetId <= 0 || $targetId === (int) $viewer->id) {
            throw new ApiException('That profile cannot be swiped.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $target = $this->candidateQuery($viewer)->find($targetId);

        if (! $target) {
            throw new ApiException('This member is not available.', 404, ApiErrorCode::NotFound->value);
        }

        return $target;
    }

    /**
     * The shared "who may this member see" base query. One place, so the deck
     * and the swipe guard can never disagree about who is visible.
     */
    private function candidateQuery(User $viewer)
    {
        $viewer->loadMissing(['addresses', 'member']);

        $query = User::query()
            ->where('user_type', 'member')
            ->whereKeyNot($viewer->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', fn ($member) => $member->where('hide_profile', 0))
            ->whereDoesntHave('profile_privacy_setting', fn ($privacy) => $privacy->where('invisible_mode', true))
            ->whereNotIn('id', HiddenProfileUser::where('hidden_from_user_id', $viewer->id)->pluck('user_id'));

        $ignoredIds = IgnoredUser::where('ignored_by', $viewer->id)->pluck('user_id')
            ->merge(IgnoredUser::where('user_id', $viewer->id)->pluck('ignored_by'))
            ->unique()
            ->values();
        $query->whereNotIn('id', $ignoredIds);

        // Opposite gender, same rule as Discover (default gender of the
        // viewer's own member record; nothing is filtered when it is unset).
        $targetGender = match ((int) ($viewer->member?->gender ?? 0)) {
            1 => 2,
            2 => 1,
            default => null,
        };

        if ($targetGender !== null) {
            $query->whereHas('member', fn ($member) => $member->where('gender', $targetGender));
        }

        return $query;
    }
}
