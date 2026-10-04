<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\MatchSuccess;
use App\Models\RewardRule;
use App\Models\RewardTransaction;
use App\Models\SatisfactionSurvey;
use App\Models\Setting;
use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompletionCenterController extends Controller
{
    public function index()
    {
        $events = Schema::hasTable('analytics_events') ? AnalyticsEvent::query() : null;
        $npsRows = Schema::hasTable('satisfaction_surveys') ? SatisfactionSurvey::query()->where('context','nps') : null;
        $nps = null;
        if ($npsRows && ($count = $npsRows->count()) > 0) {
            $promoters = (clone $npsRows)->where('score','>=',9)->count();
            $detractors = (clone $npsRows)->where('score','<=',6)->count();
            $nps = round((($promoters - $detractors) / $count) * 100, 1);
        }
        $funnelKeys = ['registration_completed','profile_completed','discovery_viewed','interest_sent','match_created','message_sent','got_match'];
        $funnel = [];
        foreach ($funnelKeys as $key) {
            $funnel[$key] = $events ? (clone $events)->where('event_name',$key)->distinct('user_id')->count('user_id') : 0;
        }
        return view('admin.completion_center.index', [
            'sponsored' => Schema::hasTable('sponsored_listings') ? SponsoredListing::latest()->paginate(20, ['*'], 'sponsored_page') : collect(),
            'rewardRules' => Schema::hasTable('reward_rules') ? RewardRule::orderBy('event_key')->get() : collect(),
            'rewardTransactions' => Schema::hasTable('reward_transactions') ? RewardTransaction::latest()->limit(20)->get() : collect(),
            'funnel' => $funnel,
            'nps' => $nps,
            'successCount' => Schema::hasTable('match_successes') ? MatchSuccess::count() : 0,
            'geo' => $events ? (clone $events)->select('country', DB::raw('COUNT(*) total'))->whereNotNull('country')->groupBy('country')->orderByDesc('total')->limit(12)->get() : collect(),
            'adSettings' => [
                'free_ads' => (bool) get_setting('ads_free_plan_enabled', 1),
                'paid_ads' => (bool) get_setting('ads_paid_plan_enabled', 0),
            ],
            'featureAdoption' => $events ? (clone $events)->select('feature_key', DB::raw('COUNT(*) total'))->whereNotNull('feature_key')->groupBy('feature_key')->orderByDesc('total')->limit(20)->get() : collect(),
            'retention' => $this->retention(),
            'users' => User::where('user_type','member')->latest()->limit(100)->get(['id','first_name','last_name','email']),
        ]);
    }

    public function storeSponsored(Request $request)
    {
        $data = $request->validate(['title'=>'required|string|max:255','sponsor_name'=>'nullable|string|max:255','target_url'=>'nullable|url|max:1000','placement'=>'required|string|max:80','priority'=>'nullable|integer|min:0','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at','user_id'=>'nullable|exists:users,id','active'=>'nullable|boolean']);
        $data['active'] = $request->boolean('active');
        SponsoredListing::create($data);
        return back()->with('success','Sponsored listing created.');
    }
    public function destroySponsored(SponsoredListing $sponsoredListing){ $sponsoredListing->delete(); return back()->with('success','Sponsored listing removed.'); }
    public function storeReward(Request $request)
    {
        $data=$request->validate(['event_key'=>'required|string|max:100','title'=>'required|string|max:255','coin_reward'=>'required|integer|min:0','max_per_user'=>'required|integer|min:1','active'=>'nullable|boolean']);
        $data['active']=$request->boolean('active'); RewardRule::updateOrCreate(['event_key'=>$data['event_key']],$data); return back()->with('success','Reward rule saved.');
    }
    public function updateAds(Request $request)
    {
        foreach(['ads_free_plan_enabled','ads_paid_plan_enabled'] as $key){ Setting::updateOrCreate(['type'=>$key],['value'=>$request->boolean($key)?1:0]); }
        return back()->with('success','Ad gating rules updated.');
    }
    private function retention(): array
    {
        if (!Schema::hasTable('users')) return ['d1'=>0,'d7'=>0,'d30'=>0];
        $cohort=User::where('user_type','member')->where('created_at','>=',now()->subDays(30)); $total=(clone $cohort)->count();
        if(!$total) return ['d1'=>0,'d7'=>0,'d30'=>0];
        return [
            'd1'=>round(((clone $cohort)->whereColumn('last_login_at','>=',DB::raw('DATE_ADD(created_at, INTERVAL 1 DAY)'))->count()/$total)*100,1),
            'd7'=>round(((clone $cohort)->whereColumn('last_login_at','>=',DB::raw('DATE_ADD(created_at, INTERVAL 7 DAY)'))->count()/$total)*100,1),
            'd30'=>round(((clone $cohort)->where('last_login_at','>=',now()->subDays(30))->count()/$total)*100,1),
        ];
    }
}
