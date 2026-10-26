<?php

namespace App\Services\Referral;

use App\Models\Member;
use App\Models\PackagePayment;
use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\NotificationHelper;
use App\Support\RegistrationReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Award a referrer once when their referral first activates a non-Basic paid plan. */
class PaidPackageReferralRewardService
{
    public const EVENT = 'referral_package_activated';

    public function award(PackagePayment $payment): bool
    {
        $issued = DB::transaction(function () use ($payment): ?array {
            $paid = PackagePayment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (!$paid || $paid->payment_status !== 'Paid'
                || (float)($paid->payable_amount ?? $paid->amount) <= 0) return null;
            $buyer = User::with('member')->find($paid->user_id);
            $package = $paid->package;
            $basic = RegistrationReward::basicPackage();
            if (!$buyer || !$package || !$basic || (int)$package->id === (int)$basic->id
                || !$buyer->referred_by || (int)$buyer->referred_by === (int)$buyer->id
                || (float)$package->price <= 0) return null;
            $referrer = User::find($buyer->referred_by);
            if (!$referrer || $referrer->user_type !== 'member' || $buyer->user_type !== 'member'
                || !$buyer->email_verified_at || (int)$buyer->approved !== 1
                || (int)($buyer->blocked ?? 0) === 1 || (int)($buyer->deactivated ?? 0) === 1) return null;
            $referrerMember = Member::where('user_id',$referrer->id)->lockForUpdate()->first();
            if (!$referrerMember) return null;
            $rule = RewardRule::where('event_key',self::EVENT)->where('active',true)->first();
            if (!$rule || (int)$rule->coin_reward <= 0 || (int)$rule->max_per_user === 0) return null;
            // One activation award per referred person, including retries,
            // payment callbacks and later purchases/upgrades.
            $existing = RewardTransaction::where('user_id',$referrer->id)
                ->where('event_key',self::EVENT)->where('reference_type','referred_user')
                ->where('reference_id',$buyer->id)->exists();
            if ($existing) return null;
            $count=RewardTransaction::where('user_id',$referrer->id)->where('event_key',self::EVENT)->count();
            $maximumRewards = (int) $rule->max_per_user;
            if ($maximumRewards !== -1 && $count >= $maximumRewards) return null;
            RewardTransaction::create([
                'user_id'=>$referrer->id,'reward_rule_id'=>$rule->id,
                'event_key'=>self::EVENT,'reference_type'=>'referred_user',
                'reference_id'=>$buyer->id,'coins'=>(int)$rule->coin_reward,
                'metadata'=>['source'=>'paid_package_activation','payment_id'=>$paid->id,'package_id'=>$package->id,'referred_user_id'=>$buyer->id],
            ]);
            $referrerMember->increment('remaining_interest',(int)$rule->coin_reward);
            return [$referrer->id,$buyer->id,$paid->id,(int)$rule->coin_reward,$package->name];
        },3);
        if (!$issued) return false;
        try {
            $referrer=User::find($issued[0]);
            if ($referrer) NotificationHelper::event($referrer,'referral_package_activation_reward',[
                'event_id'=>'paid-referral-'.$issued[1], 'info_id'=>$issued[2],
                'notify_by'=>$issued[1],'coins'=>$issued[3],'plan'=>$issued[4],
                'deep_link'=>route('member.referrals.index',[],false),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Paid referral reward notification unavailable',['referrer_id'=>$issued[0],'error'=>$e->getMessage()]);
        }
        return true;
    }
}
