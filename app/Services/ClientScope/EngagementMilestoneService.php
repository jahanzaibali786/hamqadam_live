<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\AnalyticsEvent;
use App\Models\Member;
use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Configurable, opt-in milestone rewards: unique per (member,event,reference).
 * Uses an existing RewardRule with event_key = profile_completed / first_interest.
 */
final class EngagementMilestoneService
{
    public const SUPPORTED = ['profile_completed', 'first_interest'];

    public function issue(User $user, string $event, int $referenceId): bool
    {
        if (! in_array($event, self::SUPPORTED, true) || $referenceId < 1) return false;
        if (! Schema::hasTable('reward_rules') || ! Schema::hasTable('reward_transactions')) return false;
        return DB::transaction(function () use ($user, $event, $referenceId): bool {
            $member = Member::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $member || (int) $user->approved !== 1 || ! $user->email_verified_at) return false;
            if (! $this->verifiedMilestone($user, $member, $event, $referenceId)) return false;
            $rule = RewardRule::query()->where('event_key', $event)->where('active', true)->first();
            if (! $rule || (int) $rule->coin_reward <= 0 || (int) $rule->max_per_user < 1) return false;
            $reference = ['user_id' => $user->id, 'event_key' => $event,
                'reference_type' => 'verified_milestone', 'reference_id' => $referenceId];
            if (RewardTransaction::query()->where($reference)->exists()) return false;
            if (RewardTransaction::query()->where('user_id', $user->id)->where('event_key', $event)
                ->count() >= (int) $rule->max_per_user) return false;
            RewardTransaction::query()->create($reference + [
                'reward_rule_id' => $rule->id, 'coins' => (int) $rule->coin_reward,
                'metadata' => ['source' => 'server_verified_milestone'],
            ]);
            $member->increment('remaining_interest', (int) $rule->coin_reward);
            if (Schema::hasTable('analytics_events')) {
                AnalyticsEvent::query()->create([
                    'user_id' => $user->id, 'event_name' => 'reward_issued',
                    'feature_key' => 'gamification', 'source' => 'milestone_worker',
                    'metadata' => ['event' => $event, 'coins' => (int) $rule->coin_reward],
                    'occurred_at' => now(),
                ]);
            }
            return true;
        }, 3);
    }

    private function verifiedMilestone(User $user, Member $member, string $event, int $referenceId): bool
    {
        if ($event === 'profile_completed') {
            // Reference = member ID; avoids arbitrary event references.
            return $referenceId === (int) $member->id
                && (int) $member->profile_completion_percentage >= 100;
        }
        // First interest uses the actual sent express interest record; it cannot
        // be claimed from an arbitrary client-submitted activity event.
        return (int) DB::table('express_interests')->where('interested_by',$user->id)
            ->min('id') === $referenceId;
    }
}
