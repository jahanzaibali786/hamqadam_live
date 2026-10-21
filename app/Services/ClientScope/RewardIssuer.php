<?php

namespace App\Services\ClientScope;

use App\Models\Member;
use App\Models\MatchSuccess;
use App\Models\RewardRule;
use App\Models\RewardTransaction;
use Illuminate\Support\Facades\DB;

/** Atomic rewards: issued once per event/reference within configured user limits. */
class RewardIssuer
{
    public function issue(int $userId, string $eventKey, string $referenceType, int $referenceId): bool
    {
        return DB::transaction(function () use ($userId, $eventKey, $referenceType, $referenceId): bool {
            // Match success is self-reported. Never award on a unilateral claim;
            // check for the other participant's independent confirmation.
            if ($eventKey === 'got_match') {
                if ($referenceType !== 'match_success') return false;
                $reported = MatchSuccess::query()->whereKey($referenceId)
                    ->where('user_id', $userId)->where('status', 'got_match')->first();
                if (! $reported || ! MatchSuccess::query()
                    ->where('user_id', $reported->matched_user_id)
                    ->where('matched_user_id', $userId)
                    ->where('status','got_match')->exists()) return false;
            }
            $member = Member::query()->where('user_id', $userId)->lockForUpdate()->first();
            if (! $member) return false;
            $rule = RewardRule::query()->where('event_key', $eventKey)->where('active', true)->first();
            if (! $rule || (int) $rule->coin_reward <= 0 || (int) $rule->max_per_user <= 0) return false;
            $exists = RewardTransaction::query()->where('user_id', $userId)->where('event_key', $eventKey)
                ->where('reference_type', $referenceType)->where('reference_id', $referenceId)->exists();
            if ($exists) return false;
            $count = RewardTransaction::query()->where('user_id', $userId)->where('event_key', $eventKey)->count();
            if ($count >= (int) $rule->max_per_user) return false;
            $coins = (int) $rule->coin_reward;
            $member->increment('remaining_interest', $coins);
            RewardTransaction::query()->create([
                'user_id' => $userId,
                'reward_rule_id' => $rule->id,
                'event_key' => $eventKey,
                'coins' => $coins,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => ['source' => 'mutual_user_report_not_independently_verified'],
            ]);
            return true;
        });
    }
}
