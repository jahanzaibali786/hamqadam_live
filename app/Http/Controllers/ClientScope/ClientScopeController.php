<?php

namespace App\Http\Controllers\ClientScope;

use App\Http\Controllers\Controller;
use App\Models\ClientScopeSetting;
use App\Models\MatchSuccess;
use App\Models\NotificationPreference;
use App\Models\NotificationDeliveryLog;
use App\Models\ProfileMatch;
use App\Models\RewardRule;
use App\Models\SatisfactionSurvey;
use App\Models\SponsoredListing;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ClientScope\EventRecorder;
use App\Services\ClientScope\ReportService;
use App\Services\ClientScope\RewardIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ClientScopeController extends Controller
{
    public function __construct(
        private readonly EventRecorder $events,
        private readonly ReportService $reports,
        private readonly RewardIssuer $rewards,
    ) {}

    public function portal(Request $request)
    {
        $user = $request->user();
        $matches = ProfileMatch::query()->with('matchedUser')->where('user_id', $user->id)->latest()->paginate(12);
        $confirmations = MatchSuccess::query()->where('user_id', $user->id)->where('status','got_match')->pluck('matched_user_id')->map(fn ($id) => (int) $id)->all();
        // A mutual confirmation requires both members to independently report the match.
        $mutualConfirmations = MatchSuccess::query()->where('matched_user_id', $user->id)
            ->where('status','got_match')->whereIn('user_id', $confirmations)
            ->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $sponsors = $this->visibleSponsors($user);
        $link = route('profile.share', $user->id);
        return view('client_scope.portal', compact('matches','confirmations','mutualConfirmations','sponsors','link'));
    }

    public function support(Request $request)
    {
        $tickets = SupportTicket::query()->where('sender_user_id', $request->user()->id)
            ->with(['replies' => fn ($q) => $q->oldest()])->latest()->paginate(15);
        return view('client_scope.support', compact('tickets'));
    }

    public function satisfaction(Request $request)
    {
        $previous = SatisfactionSurvey::query()->where('user_id', $request->user()->id)
            ->where('context','nps')->latest()->first();
        return view('client_scope.satisfaction', compact('previous'));
    }

    public function submitSatisfaction(Request $request)
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'between:0,10'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);
        DB::transaction(function () use ($request, $data): void {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $recent = SatisfactionSurvey::query()->where('user_id', $request->user()->id)
                ->where('context','nps')->where('created_at','>=', now()->subDays(30))->exists();
            if ($recent) throw ValidationException::withMessages(['score' => 'You have already completed this survey within the last 30 days.']);
            SatisfactionSurvey::query()->create([
                'user_id' => $request->user()->id,
                'score' => (int) $data['score'],
                'comment' => $data['comment'] ?? null,
                'context' => 'nps',
                'platform' => 'web',
            ]);
        });
        $this->events->record($request, 'nps_submitted', 'satisfaction');
        return redirect()->route('client.scope.satisfaction')->with('success','Your feedback was recorded.');
    }

    public function gotMatch(Request $request)
    {
        $data = $request->validate(['matched_user_id' => ['required','integer','exists:users,id']]);
        $id = (int) $data['matched_user_id'];
        abort_if($id === (int) $request->user()->id, 422, 'You cannot match with your own account.');
        $match = ProfileMatch::query()->where('user_id', $request->user()->id)->where('match_id', $id)->first();
        abort_unless($match, 403, 'This profile has not been matched with your account.');

        $created = false;
        $mutual = false;
        $confirmation = DB::transaction(function () use ($request, $id, $match, &$created, &$mutual): MatchSuccess {
            // Lock both accounts in stable order: concurrent reciprocal submissions
            // cannot separately decide the match is still one-sided.
            $ids = [(int) $request->user()->id, $id];
            sort($ids, SORT_NUMERIC);
            User::query()->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
            $existing = MatchSuccess::query()->where('user_id',$request->user()->id)
                ->where('matched_user_id',$id)->where('status','got_match')->first();
            if ($existing) {
                $confirmation = $existing;
            } else {
                $confirmation = MatchSuccess::query()->create([
                    'user_id' => $request->user()->id,
                    'matched_user_id' => $id,
                    'profile_match_id' => $match->id,
                    'status' => 'got_match',
                    'confirmed_at' => now(),
                ]);
                $created = true;
            }
            $mutual = MatchSuccess::query()->where('user_id',$id)
                ->where('matched_user_id',$request->user()->id)->where('status','got_match')->exists();
            return $confirmation;
        });
        if ($created) {
            $this->events->record($request,'got_match_confirmed','match_tracking', [
                'profile_match_id' => $match->id, 'mutual' => $mutual,
            ]);
        }
        if ($mutual) {
            // No coin award for an uncorroborated report. The reward ledger
            // independently enforces one award per member/reference.
            $this->rewards->issue((int) $request->user()->id, 'got_match', 'match_success', (int) $confirmation->id);
            $other = MatchSuccess::query()->where('user_id',$id)
                ->where('matched_user_id',$request->user()->id)->where('status','got_match')->first();
            if ($other) $this->rewards->issue($id, 'got_match', 'match_success', (int) $other->id);
        }
        return redirect()->route('client.scope.portal')->with('success',
            $mutual ? 'Both members have confirmed this match. This is not independently verified as a marriage outcome.'
                    : 'Your confirmation is recorded. Awaiting confirmation from the other member.');
    }

    public function sponsoredVisit(Request $request, SponsoredListing $sponsor)
    {
        $visible = $this->visibleSponsors($request->user())->contains('id', $sponsor->id);
        abort_unless($visible, 404);
        SponsoredListing::query()->whereKey($sponsor->id)->increment('clicks');
        $this->events->record($request, 'sponsored_clicked', 'sponsored_listing', ['listing_id'=>$sponsor->id]);
        $url = (string) $sponsor->target_url;
        if (preg_match('~^https?://~i', $url)) return redirect()->away($url);
        if ($sponsor->user_id) return redirect()->route('profile.share', $sponsor->user_id);
        return redirect()->route('client.scope.portal');
    }

    /** Count an actual client-observed sponsor exposure at most once/user/hour. */
    public function sponsoredImpression(Request $request, SponsoredListing $sponsor)
    {
        abort_unless($this->visibleSponsors($request->user())->contains('id', $sponsor->id), 404);
        $key = 'sponsor-impression:'.hash('sha256', $request->user()->id.':'.$sponsor->id);
        if (Cache::add($key, 1, 3600)) {
            SponsoredListing::query()->whereKey($sponsor->id)->increment('impressions');
            $this->events->record($request, 'sponsored_impression', 'sponsored_listing', ['listing_id' => $sponsor->id]);
        }
        return response()->json(['success' => true]);
    }

    private function visibleSponsors(User $user)
    {
        // A free package can also have a current_package_id; classify paid
        // entitlement using actual plan price, not the presence of any package.
        $package = $user->member?->package;
        $isPaid = $package && (float) $package->price > 0;
        if ($isPaid && ClientScopeSetting::valueOf('show_ads_to_paid','0') !== '1') return collect();
        $packageId = $user->member?->current_package_id;
        return SponsoredListing::live()->orderByDesc('priority')->limit(30)->get()
            ->filter(fn (SponsoredListing $s) => empty($s->eligible_plan_ids)
                || ($packageId && in_array((int) $packageId, array_map('intval',(array)$s->eligible_plan_ids),true)))
            ->take(6)->values();
    }

    public function notifications(Request $request)
    {
        $preferences = NotificationPreference::query()->firstOrCreate(['user_id'=>$request->user()->id]);
        $history = NotificationDeliveryLog::query()->where('user_id',$request->user()->id)
            ->latest('id')->paginate(20);
        return view('client_scope.notifications', compact('preferences','history'));
    }

    public function saveNotifications(Request $request)
    {
        $data = $request->validate([
            'timezone' => ['required','timezone'],
            'quiet_hours_start' => ['nullable','date_format:H:i'],
            'quiet_hours_end' => ['nullable','date_format:H:i'],
            'reminder_interval_minutes' => ['required','integer','between:60,10080'],
            'reminder_max_attempts' => ['required','integer','between:0,10'],
            'digest_frequency' => ['required','in:instant,daily,weekly,off'],
        ]);
        if (boolval($data['quiet_hours_start'] ?? null) !== boolval($data['quiet_hours_end'] ?? null)) {
            throw ValidationException::withMessages(['quiet_hours_start'=>'Set both a quiet-hours start and end, or leave both blank.']);
        }
        $data['push_enabled'] = $request->boolean('push_enabled');
        $data['email_enabled'] = $request->boolean('email_enabled');
        $data['engagement_enabled'] = $request->boolean('engagement_enabled');
        NotificationPreference::query()->updateOrCreate(['user_id'=>$request->user()->id],$data);
        return back()->with('success','Notification preferences saved.');
    }

    public function adminProfiles(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable','string','max:120'],
            'gender' => ['nullable','in:1,2'],
            'country_id' => ['nullable','integer','exists:countries,id'],
            'city_id' => ['nullable','integer','exists:cities,id'],
            'activity' => ['nullable','in:active_7d,active_30d,never_active'],
            'visibility' => ['nullable','in:visible,hidden'],
            'sort' => ['nullable','in:newest,oldest,name,last_active'],
        ]);
        $query = User::query()->with('member')->where('user_type','member');
        $needle = trim((string)($data['q'] ?? ''));
        if ($needle !== '') {
            $escaped = addcslashes($needle, '%_\\');
            $query->where(function ($q) use ($escaped, $needle): void {
                $q->where('name','like','%'.$escaped.'%')
                    ->orWhere('first_name','like','%'.$escaped.'%')
                    ->orWhere('last_name','like','%'.$escaped.'%')
                    ->orWhere('email','like','%'.$escaped.'%')
                    ->orWhere('phone','like','%'.$escaped.'%')
                    ->orWhere('code','like','%'.$escaped.'%');
                if (ctype_digit($needle)) $q->orWhere('id',(int) $needle);
            });
        }
        if (isset($data['gender'])) $query->whereHas('member',fn($q) => $q->where('gender',$data['gender']));
        if (isset($data['country_id']) || isset($data['city_id'])) {
            $query->whereHas('addresses',function($q) use ($data) {
                if (isset($data['country_id'])) $q->where('country_id', (int) $data['country_id']);
                if (isset($data['city_id'])) $q->where('city_id', (int) $data['city_id']);
            });
        }
        if (($data['activity'] ?? '') === 'active_7d') $query->where('last_active_at','>=',now()->subDays(7));
        elseif (($data['activity'] ?? '') === 'active_30d') $query->where('last_active_at','>=',now()->subDays(30));
        elseif (($data['activity'] ?? '') === 'never_active') $query->whereNull('last_active_at');
        if (($data['visibility'] ?? '') === 'hidden') $query->whereHas('member',fn($q) => $q->where('hide_profile',true));
        elseif (($data['visibility'] ?? '') === 'visible') $query->whereHas('member',fn($q) => $q->where(fn($q) => $q->where('hide_profile',false)->orWhereNull('hide_profile')));
        if (($data['sort'] ?? '') === 'name') $query->orderBy('name');
        elseif (($data['sort'] ?? '') === 'oldest') $query->oldest();
        elseif (($data['sort'] ?? '') === 'last_active') $query->orderByDesc('last_active_at');
        else $query->latest();
        $profiles = $query->paginate(25)->withQueryString();
        return view('admin.client_scope.profiles',compact('profiles'));
    }

    public function adminDashboard()
    {
        $report = $this->reports->build();
        $sponsors = SponsoredListing::query()->latest()->paginate(20);
        $rewardRules = RewardRule::query()->orderBy('event_key')->get();
        $ratings = Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets','rating')
            ? SupportTicket::query()->whereNotNull('rating')->latest('rated_at')->limit(30)->get() : collect();
        $paidAds = ClientScopeSetting::valueOf('show_ads_to_paid','0') === '1';
        $notificationStatus = Schema::hasTable('notification_delivery_logs')
            ? NotificationDeliveryLog::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status')
            : collect();
        return view('admin.client_scope.dashboard', compact('report','sponsors','rewardRules','ratings','paidAds','notificationStatus'));
    }

    public function setOptions(Request $request)
    {
        $request->validate(['show_ads_to_paid'=>['nullable','boolean']]);
        ClientScopeSetting::query()->updateOrCreate(['key'=>'show_ads_to_paid'], ['value'=>$request->boolean('show_ads_to_paid') ? '1' : '0']);
        return back()->with('success','Advertisement entitlement settings saved.');
    }

    public function storeSponsor(Request $request)
    {
        $data = $this->sponsorData($request);
        SponsoredListing::query()->create($data);
        return back()->with('success','Sponsored listing created.');
    }

    public function updateSponsor(Request $request, SponsoredListing $sponsor)
    {
        $data = $this->sponsorData($request);
        $sponsor->update($data);
        return back()->with('success','Sponsored listing updated.');
    }

    public function removeSponsor(SponsoredListing $sponsor)
    {
        $sponsor->update(['active'=>false]);
        return back()->with('success','Sponsored listing disabled.');
    }

    private function sponsorData(Request $request): array
    {
        $data = $request->validate([
            'title'=>['required','string','max:150'],
            'sponsor_name'=>['nullable','string','max:120'],
            'user_id'=>['nullable','integer','exists:users,id'],
            'image'=>['nullable','string','max:255'],
            'target_url'=>['nullable','url','max:2000','regex:/^https?:\/\//i'],
            'placement'=>['required','in:discovery,home,match'],
            'priority'=>['required','integer','between:0,100'],
            'starts_at'=>['nullable','date'],
            'ends_at'=>['nullable','date','after_or_equal:starts_at'],
            'eligible_plan_ids'=>['nullable','array','max:30'],
            'eligible_plan_ids.*'=>['integer','exists:packages,id'],
        ]);
        $data['active'] = $request->boolean('active');
        return $data;
    }

    public function saveRewardRule(Request $request)
    {
        $data = $request->validate([
            'event_key'=>['required','in:got_match'],
            'title'=>['required','string','max:150'],
            'coin_reward'=>['required','integer','between:0,1000'],
            'max_per_user'=>['required','integer','between:1,20'],
        ]);
        $data['active'] = $request->boolean('active');
        RewardRule::query()->updateOrCreate(['event_key'=>$data['event_key']],$data);
        return back()->with('success','Reward rule saved.');
    }
}
