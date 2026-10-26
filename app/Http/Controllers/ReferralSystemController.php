<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReferralSystemController extends Controller
{
    public function __construct(private readonly ReferralRewardService $rewards)
    {
    }

    /** Admin referral settings and referral overview. */
    public function set_referral_commission()
    {
        Log::info('Referral settings page requested', [
            'user_id' => auth()->id(),
            'user_type' => auth()->user()?->user_type,
            'route' => request()->route()?->getName(),
        ]);

        // The settings page must be usable independently of the report:
        // dashboard reward-history queries used to fail the entire screen
        // when schema migrations were pending or old tables were incomplete.
        $rewardRule = null;
        $packageRewardRule = null;
        $issues = [];

        try {
            $required = [
                'reward_rules' => ['id', 'event_key', 'coin_reward', 'max_per_user', 'active'],
                'reward_transactions' => ['id', 'user_id', 'event_key', 'reference_type', 'reference_id', 'coins'],
            ];
            foreach ($required as $table => $columns) {
                if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                    $issues[] = "Missing {$table} table.";
                    continue;
                }
                foreach ($columns as $column) {
                    if (! \Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                        $issues[] = "Missing {$table}.{$column} column.";
                    }
                }
            }
            if ($issues === []) {
                $rewardRule = RewardRule::query()
                    ->where('event_key', ReferralRewardService::EVENT)->first();
                $packageRewardRule = RewardRule::query()
                    ->where('event_key', \App\Services\Referral\PaidPackageReferralRewardService::EVENT)->first();
            }
        } catch (\Throwable $exception) {
            Log::error('Referral settings read failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            $issues[] = 'The database could not be read. Check the Laravel error log.';
        }

        Log::info('Referral settings data prepared', [
            'user_id' => auth()->id(),
            'database_ready' => $issues === [],
            'setup_issues' => $issues,
            'has_registration_rule' => $rewardRule !== null,
            'has_package_rule' => $packageRewardRule !== null,
        ]);

        try {
            // Always render a usable admin page with an explicit diagnostic.
            // Sending HTTP 503 here can be reduced to a blank page by proxies.
            return response()->view('admin.referral.settings', [
                'rewardRule' => $rewardRule,
                'packageRewardRule' => $packageRewardRule,
                'databaseReady' => $issues === [],
                'setupIssues' => $issues,
            ], 200)->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $exception) {
            Log::critical('Referral settings view failed to render', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function index()
    {
        return $this->adminDashboard();
    }

    private function adminDashboard()
    {
        if (! Schema::hasTable('reward_rules') || ! Schema::hasTable('reward_transactions')) {
            return response()->view('admin.referral.setup_required', [], 503);
        }
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
        abort_unless(Schema::hasTable('reward_rules') && Schema::hasTable('reward_transactions'), 503, 'Referral reward tables require migration.');
        $data = $request->validate([
            'coin_reward' => ['required','integer','between:0,1000'],
            'max_per_user' => ['required','integer','between:-1,10000'],
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
        abort_unless(Schema::hasTable('reward_rules') && Schema::hasTable('reward_transactions'), 503, 'Referral reward tables require migration.');
        $data=$request->validate([
            'coin_reward'=>['required','integer','between:0,1000'],
            'max_per_user'=>['required','integer','between:-1,10000'],
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
