<?php

declare(strict_types=1);

namespace App\Services\Referral;

use App\Models\Member;
use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Reconciles successful referrals into coin rewards with a durable audit log. */
class ReferralRewardService
{
    public const EVENT = 'referral_verified';
    public const PACKAGE_EVENT = 'referral_package_activated';

    public function rule(): ?RewardRule
    {
        return RewardRule::query()->where('event_key', self::EVENT)->first();
    }

    public function referralCode(User $user): string
    {
        if (filled($user->code)) {
            return (string) $user->code;
        }
        return DB::transaction(function () use ($user): string {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (filled($account->code)) return (string) $account->code;
            do {
                $code = 'HQ-' . Str::upper(Str::random(10));
            } while (User::where('code', $code)->exists());
            $account->forceFill(['code' => $code])->save();
            return $code;
        });
    }

    public function isEligible(User $referrer, User $referred): bool
    {
        if ((int) $referrer->id === (int) $referred->id || (int) $referred->referred_by !== (int) $referrer->id) return false;
        if ($referrer->user_type !== 'member' || $referred->user_type !== 'member') return false;
        if (! $referred->email_verified_at || (int) $referred->approved !== 1) return false;
        if ((int)($referred->blocked ?? 0) === 1 || (int)($referred->deactivated ?? 0) === 1) return false;
        if (filled($referrer->email) && filled($referred->email)
            && strcasecmp(trim($referrer->email), trim($referred->email)) === 0) return false;
        if (filled($referrer->phone) && filled($referred->phone)
            && preg_replace('/\D+/', '', $referrer->phone) === preg_replace('/\D+/', '', $referred->phone)) return false;
        // Prevent duplicate account farms under one code from collecting twice
        // for the same verified email / phone.
        if (filled($referred->phone) && User::where('referred_by', $referrer->id)
            ->where('id', '<', $referred->id)->where('phone', $referred->phone)->exists()) return false;
        if (filled($referred->email) && User::where('referred_by', $referrer->id)
            ->where('id', '<', $referred->id)->where('email', $referred->email)->exists()) return false;

        $member = $referred->member;
        if (! $member) return false;
        // New onboarding flow tracks a completed registration explicitly.
        // The check prevents a partially created/OTP-only account from paying.
        if (Schema::hasColumn('members', 'registration_completed_at') && ! $member->registration_completed_at) return false;
        return true;
    }

    public function award(User $referrer, User $referred): bool
    {
        $rule = $this->rule();
        if (! $rule || ! $rule->active || (int) $rule->coin_reward <= 0) return false;
        if (! $this->isEligible($referrer, $referred)) return false;

        $awarded=DB::transaction(function () use ($referrer, $referred): bool {
            // Serialise rewards per referrer, even when scheduler and web/API
            // requests arrive simultaneously.
            $member = Member::where('user_id', $referrer->id)->lockForUpdate()->first();
            if (! $member) return false;
            $rule = RewardRule::where('event_key', self::EVENT)->where('active', true)->first();
            if (! $rule || (int)$rule->coin_reward <= 0) return false;
            $reference = ['user_id' => $referrer->id, 'event_key' => self::EVENT,
                'reference_type' => 'referred_user', 'reference_id' => $referred->id];
            if (RewardTransaction::where($reference)->exists()) return false;
            $count = RewardTransaction::where('user_id', $referrer->id)->where('event_key', self::EVENT)->count();
            $maximumRewards = (int) $rule->max_per_user;
            if ($maximumRewards !== -1 && $count >= $maximumRewards) return false;
            // Re-check ownership and verification within the same transaction.
            $fresh = User::with('member')->find($referred->id);
            if (! $fresh || ! $this->isEligible($referrer, $fresh)) return false;
            RewardTransaction::create($reference + [
                'reward_rule_id' => $rule->id, 'coins' => (int) $rule->coin_reward,
                'metadata' => ['source' => 'verified_referral', 'referred_user_id' => $fresh->id],
            ]);
            $member->increment('remaining_interest', (int) $rule->coin_reward);
            return true;
        }, 3);
        if ($awarded) {
            try {
                \App\Services\NotificationHelper::event($referrer, 'referral_registration_reward', [
                    'event_id'=>'registration-referral-'.$referred->id,
                    'info_id'=>$referred->id,
                    'notify_by'=>$referred->id,
                    'coins'=>(int)$rule->coin_reward,
                    'deep_link'=>route('member.referrals.index',[],false),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Registration referral notice failed', ['referrer_id'=>$referrer->id]);
            }
        }
        return $awarded;
    }

    /** Reconcile a bounded page without rewarding unverified accounts. */
    public function reconcile(User $referrer, int $limit = 200): int
    {
        $rule = $this->rule();
        if (! $rule || ! $rule->active || (int)$rule->coin_reward <= 0) return 0;
        $awards = 0;
        User::query()->with('member')->where('referred_by', $referrer->id)
            ->where('user_type', 'member')->whereNotNull('email_verified_at')
            ->where('approved', 1)->orderBy('id')->limit(max(1,min(1000,$limit)))
            ->get()->each(function (User $candidate) use ($referrer, &$awards) {
                if ($this->award($referrer, $candidate)) $awards++;
            });
        return $awards;
    }

    public function summary(User $referrer, bool $sync = false): array
    {
        if ($sync) $this->reconcile($referrer);
        $code = $this->referralCode($referrer);
        $total = User::where('referred_by', $referrer->id)->count();
        $awarded = RewardTransaction::where('user_id', $referrer->id)
            ->where('event_key', self::EVENT);
        $count = (clone $awarded)->count();
        $packageTransactions=RewardTransaction::where('user_id',$referrer->id)->where('event_key',self::PACKAGE_EVENT);
        $packageCount=(clone $packageTransactions)->count();
        $packageCoins=(int)(clone $packageTransactions)->sum('coins');
        $packageRule=RewardRule::where('event_key',self::PACKAGE_EVENT)->first();
        return [
            'referral_code' => $code,
            'referral_url' => route('register', ['referral_code' => $code]),
            'total_referrals' => $total,
            'rewarded_referrals' => $count,
            'pending_referrals' => max(0, $total - $count),
            'registration_coins_earned' => (int) (clone $awarded)->sum('coins'),
            'package_coins_earned' => $packageCoins,
            'package_activation_rewards' => $packageCount,
            'coins_earned' => (int) (clone $awarded)->sum('coins') + $packageCoins,
            'remaining_coins' => (int) ($referrer->fresh('member')->member?->remaining_interest ?? 0),
            'reward_rule' => ($rule = $this->rule()) ? [
                'active' => (bool)$rule->active, 'coins_per_referral' => (int)$rule->coin_reward,
                'max_rewards_per_member' => (int)$rule->max_per_user,
            ] : ['active' => false, 'coins_per_referral' => 0, 'max_rewards_per_member' => 0],
            'package_reward_rule' => [
                'active'=>(bool)($packageRule?->active ?? false),
                'coins_per_activation'=>(int)($packageRule?->coin_reward ?? 0),
                'max_rewards_per_member'=>(int)($packageRule?->max_per_user ?? 0),
            ],
            'eligibility' => 'Registration: verified eligible account. Package: first qualifying paid non-Basic package activation; once per referred member.',
        ];
    }
}
