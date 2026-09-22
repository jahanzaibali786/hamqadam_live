<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatThread;
use App\Models\Member;
use App\Models\ReportedUser;
use App\Models\SafetyAction;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Schema;

class BadgeService
{
    private const TRUST_DAYS = 7;

    public function refresh(User $user): void
    {
        $member = $user->member;

        if (! $member) {
            return;
        }

        $verified = $this->isVerified($member);
        $trustEligible = $this->isTrustEligible($user);
        $wasVerified = (bool) $member->verification_badge;
        $wasTrust = (bool) $member->trust_badge;

        $member->forceFill([
            'verification_badge' => $verified,
            'verification_badge_earned_at' => $verified
                ? ($member->verification_badge_earned_at ?: now())
                : null,
            'trust_badge' => $trustEligible,
            'trust_badge_earned_at' => $trustEligible
                ? ($member->trust_badge_earned_at ?: now())
                : null,
        ])->saveQuietly();

        if (! $wasVerified && $verified) {
            $this->notify($user, 'Verification Badge', 'Your identity has been verified. Your Verification Badge is now active.');
        }

        if (! $wasTrust && $trustEligible) {
            $this->notify($user, 'Trust Badge', 'Congratulations! You completed 7 consecutive daily logins without report or block activity. Your Trust Badge is now active.');
        }
    }

    private function notify(User $user, string $badge, string $message): void
    {
        try {
            $user->notify(new \App\Notifications\BadgeEarnedNotification($badge, $message));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Badge notification delivery failed.', [
                'user_id' => $user->id,
                'badge' => $badge,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function isVerified(?Member $member): bool
    {
        return $member !== null
            && ($member->verification_status === 'verified'
                || $member->ai_verification_status === 'approved');
    }

    public function isTrustEligible(User $user): bool
    {
        if (! Schema::hasTable('user_activity_logs')) {
            return false;
        }

        $start = now()->subDays(self::TRUST_DAYS - 1)->startOfDay();
        $end = now()->endOfDay();
        $loginDays = UserActivityLog::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('DATE(occurred_at) as login_day')
            ->distinct()
            ->pluck('login_day')
            ->map(fn ($day): string => (string) $day)
            ->all();

        $expectedDays = [];
        for ($day = $start->copy(); $day->lte(now()->startOfDay()); $day->addDay()) {
            $expectedDays[] = $day->toDateString();
        }

        if (count(array_intersect($expectedDays, $loginDays)) !== self::TRUST_DAYS) {
            return false;
        }

        if (Schema::hasTable('reported_users') && ReportedUser::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)->orWhere('reported_by', $user->id);
            })
            ->whereBetween('created_at', [$start, $end])
            ->exists()) {
            return false;
        }

        if (Schema::hasTable('safety_actions') && SafetyAction::query()
            ->where(function ($query) use ($user): void {
                $query->where('target_user_id', $user->id)->orWhere('actor_user_id', $user->id);
            })
            ->whereIn('action_type', ['report', 'reported', 'block', 'blocked'])
            ->whereBetween('created_at', [$start, $end])
            ->exists()) {
            return false;
        }

        if (Schema::hasTable('chat_threads') && ChatThread::query()
            ->where(function ($query) use ($user): void {
                $query->where('sender_user_id', $user->id)->orWhere('receiver_user_id', $user->id);
            })
            ->whereNotNull('blocked_by_user')
            ->exists()) {
            return false;
        }

        return true;
    }

    public function trustStreak(?User $user): int
    {
        if (! $user || ! Schema::hasTable('user_activity_logs')) {
            return 0;
        }

        $days = UserActivityLog::query()
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->orderByDesc('occurred_at')
            ->get(['occurred_at'])
            ->map(fn (UserActivityLog $log): string => $log->occurred_at->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($days === []) {
            return 0;
        }

        $today = now()->startOfDay();
        $latestLoginDay = \Carbon\Carbon::parse($days[0])->startOfDay();

        // The current day is still active; a full missed day resets the streak.
        if ($latestLoginDay->lt($today->copy()->subDay())) {
            return 0;
        }

        $streak = 0;
        $cursor = $latestLoginDay->copy();
        $loggedDays = array_fill_keys($days, true);

        while (isset($loggedDays[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return min($streak, self::TRUST_DAYS);
    }
    public function payload(?User $user): array
    {
        $member = $user?->member;
        $verified = $this->isVerified($member);

        return [
            'trust' => [
                'earned' => (bool) ($member?->trust_badge ?? false),
                'name' => 'Trust Badge',
                'requirement' => '7 consecutive daily logins with no report or block activity',
                'earned_at' => optional($member?->trust_badge_earned_at)->toISOString(),
                'current_streak' => $this->trustStreak($user),
                'target_streak' => self::TRUST_DAYS,
            ],
            'verification' => [
                'earned' => $verified,
                'name' => 'Verification Badge',
                'source' => $member?->ai_verification_status === 'approved' ? 'ai_model' : ($verified ? 'manual_review' : null),
                'earned_at' => optional($member?->verification_badge_earned_at ?? $member?->ai_verified_at)->toISOString(),
            ],
        ];
    }
}
