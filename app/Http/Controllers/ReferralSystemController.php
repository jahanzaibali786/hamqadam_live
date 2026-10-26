<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Http\Request;

class ReferralSystemController extends Controller
{
    public function __construct(private readonly ReferralRewardService $rewards)
    {
    }

    private function authorizeRewardSettings(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->user_type === 'admin' || $user->can('set_referral_commission')), 403);
    }

    /** Admin referral settings and referral overview. */
    public function set_referral_commission()
    {
        $this->authorizeRewardSettings();
        return $this->adminDashboard();
    }

    public function index()
    {
        return $this->adminDashboard();
    }

    private function adminDashboard()
    {
        return view('admin.referral.index', [
            'referrals' => User::with('member')->whereNotNull('referred_by')->latest()->paginate(20),
            'rewardRule' => $this->rewards->rule(),
            'packageRewardRule' => RewardRule::where('event_key', \App\Services\Referral\PaidPackageReferralRewardService::EVENT)->first(),
            'totalReferrals' => User::whereNotNull('referred_by')->count(),
            'totalCoins' => (int) RewardTransaction::whereIn('event_key',[ReferralRewardService::EVENT,\App\Services\Referral\PaidPackageReferralRewardService::EVENT])->sum('coins'),
            'rewardHistory' => RewardTransaction::whereIn('event_key',[ReferralRewardService::EVENT,\App\Services\Referral\PaidPackageReferralRewardService::EVENT])
                ->latest()->limit(25)->get(),
        ]);
    }

    public function save_reward_settings(Request $request)
    {
        $this->authorizeRewardSettings();
        $data = $request->validate([
            'coin_reward' => ['required','integer','between:0,1000'],
            'max_per_user' => ['required','integer','between:1,10000'],
            'active' => ['nullable','boolean'],
        ]);
        RewardRule::updateOrCreate(['event_key' => ReferralRewardService::EVENT], [
            'title' => 'Verified referral completion',
            'coin_reward' => (int) $data['coin_reward'],
            'max_per_user' => (int) $data['max_per_user'],
            'active' => $request->boolean('active') && (int) $data['coin_reward'] > 0,
            'conditions' => ['requires_email_verification' => true, 'requires_completed_registration' => true],
        ]);
        return back()->with('success', 'Referral reward settings saved. Eligible rewards will be reconciled by scheduler.');
    }

    public function save_package_reward_settings(Request $request)
    {
        $this->authorizeRewardSettings();
        $data=$request->validate([
            'coin_reward'=>['required','integer','between:0,1000'],
            'max_per_user'=>['required','integer','between:1,10000'],
            'active'=>['nullable','boolean'],
        ]);
        RewardRule::updateOrCreate(['event_key'=>\App\Services\Referral\PaidPackageReferralRewardService::EVENT],[
            'title'=>'First qualifying paid package activation referral',
            'coin_reward'=>(int)$data['coin_reward'],
            'max_per_user'=>(int)$data['max_per_user'],
            'active'=>$request->boolean('active') && (int)$data['coin_reward']>0,
            'conditions'=>['paid_only'=>true,'exclude_registration_basic'=>true,'once_per_referred_user'=>true],
        ]);
        return back()->with('success','Paid package referral reward settings saved.');
    }

    public function referal_earnings_admin()
    {
        return view('admin.referral.index', [
            'referrals' => User::whereNotNull('referred_by')->latest()->paginate(20),
            'rewardRule' => $this->rewards->rule(),
            'packageRewardRule' => RewardRule::where('event_key', \App\Services\Referral\PaidPackageReferralRewardService::EVENT)->first(),
            'totalReferrals' => User::whereNotNull('referred_by')->count(),
            'totalCoins' => (int) RewardTransaction::where('event_key', ReferralRewardService::EVENT)->sum('coins'),
            'rewardHistory' => RewardTransaction::whereIn('event_key',[ReferralRewardService::EVENT,\App\Services\Referral\PaidPackageReferralRewardService::EVENT])->latest()->limit(25)->get(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $member = $request->user();
        $summary = $this->rewards->summary($member, true);
        $referrals = User::where('referred_by', $member->id)->latest()->paginate(15, ['id','first_name','last_name','created_at','email_verified_at','approved','user_type','blocked','deactivated']);
        $rewardedIds = RewardTransaction::where('user_id', $member->id)
            ->where('event_key', ReferralRewardService::EVENT)
            ->where('reference_type', 'referred_user')
            ->whereIn('reference_id', $referrals->getCollection()->pluck('id'))->pluck('reference_id')
            ->map(fn ($id) => (int) $id)->all();
        $history = RewardTransaction::where('user_id', $member->id)
            ->whereIn('event_key',[ReferralRewardService::EVENT, \App\Services\Referral\PaidPackageReferralRewardService::EVENT])->latest()->paginate(15, ['*'], 'rewards_page');
        $commissions = Wallet::where('user_id', $member->id)
            ->where('payment_method', 'reffered_commission')->latest()->limit(10)->get();
        $packageRewardedIds=RewardTransaction::where('user_id',$member->id)->where('event_key',\App\Services\Referral\PaidPackageReferralRewardService::EVENT)->where('reference_type','referred_user')->whereIn('reference_id',$referrals->getCollection()->pluck('id'))->pluck('reference_id')->map(fn($id)=>(int)$id)->all();
        return view('frontend.member.referrals.index', compact('summary','referrals','history','commissions','rewardedIds','packageRewardedIds'));
    }

    public function my_referred_users(Request $request)
    {
        return $this->dashboard($request);
    }

    public function my_referral_earnings(Request $request)
    {
        return $this->dashboard($request);
    }
}
