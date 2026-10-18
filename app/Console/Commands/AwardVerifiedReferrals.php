<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RewardRule;
use App\Models\User;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Console\Command;

class AwardVerifiedReferrals extends Command
{
    protected $signature = 'referrals:award-verified {--limit=500}';
    protected $description = 'Grant configured, idempotent coin rewards for eligible completed referrals.';

    public function handle(ReferralRewardService $rewards): int
    {
        $rule = RewardRule::where('event_key', ReferralRewardService::EVENT)->where('active', true)->first();
        if (! $rule || (int)$rule->coin_reward < 1) {
            $this->info('Referral reward rule is disabled or not configured.');
            return self::SUCCESS;
        }
        $limit = max(1, min(5000, (int)$this->option('limit')));
        $count = 0;
        $referrers = User::query()->whereIn('id', function ($q) {
            $q->select('referred_by')->from('users')->whereNotNull('referred_by');
        })->where('user_type', 'member')->orderBy('id')->limit($limit)->get();
        foreach ($referrers as $referrer) {
            $count += $rewards->reconcile($referrer, 1000);
        }
        $this->info('Referral rewards credited: '.$count);
        return self::SUCCESS;
    }
}
