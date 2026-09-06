<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpressInterest;
use App\Models\PackagePayment;
use App\Models\ProfileMatch;
use App\Models\ProfileViewer;
use App\Models\Shortlist;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserDeviceSession;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UserActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_user_activity']);
    }
    public function index(): View
    {
        $sortSearch = request('search');
        $status = request('status');

        $members = User::with(['member.package'])
            ->where('user_type', 'member')
            ->when($sortSearch, function ($query) use ($sortSearch) {
                $query->where(function ($q) use ($sortSearch) {
                    $q->where('code', $sortSearch)
                        ->orWhere('first_name', 'like', '%' . $sortSearch . '%')
                        ->orWhere('last_name', 'like', '%' . $sortSearch . '%')
                        ->orWhere('email', 'like', '%' . $sortSearch . '%')
                        ->orWhere('phone', 'like', '%' . $sortSearch . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ['%' . $sortSearch . '%']);
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('blocked', 0)->where('deactivated', 0))
            ->when($status === 'inactive', fn ($query) => $query->where(function ($q) {
                $q->whereNull('last_activity')->orWhere('last_activity', '<', now()->subDays(14)->timestamp);
            }))
            ->when($status === 'blocked', fn ($query) => $query->where('blocked', 1))
            ->when($status === 'pending', fn ($query) => $query->where('approved', 0))
            ->latest()
            ->paginate(20);

        $this->decorateActivityIndexMembers($members->getCollection());

        return view('admin.members.activity_index', compact('members', 'sortSearch', 'status'));
    }

    private function decorateActivityIndexMembers(Collection $members): void
    {
        if ($members->isEmpty()) {
            return;
        }

        $ids = $members->pluck('id')->all();

        $lastLogs = Schema::hasTable('user_activity_logs')
            ? UserActivityLog::query()
                ->whereIn('user_id', $ids)
                ->selectRaw('user_id, MAX(occurred_at) as last_seen_at, COUNT(*) as activity_count')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $lastSessions = Schema::hasTable('user_device_sessions')
            ? UserDeviceSession::query()
                ->whereIn('user_id', $ids)
                ->selectRaw('user_id, MAX(last_used_at) as last_seen_at, COUNT(*) as session_count')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $members->each(function (User $member) use ($lastLogs, $lastSessions) {
            $candidates = collect([
                $member->last_activity ? Carbon::createFromTimestamp((int) $member->last_activity) : null,
                optional($lastLogs->get($member->id))->last_seen_at,
                optional($lastSessions->get($member->id))->last_seen_at,
                $member->updated_at,
            ])->filter()->map(fn ($value) => Carbon::parse($value));

            $member->activity_last_seen_at = $candidates->sortDesc()->first();
            $member->activity_session_count = (int) (optional($lastLogs->get($member->id))->activity_count ?? 0)
                + (int) (optional($lastSessions->get($member->id))->session_count ?? 0);
        });
    }

    public function show($id): View
    {
        $user = User::with([
            'member.package',
            'partner_expectations',
            'profile_verification_requests.documents',
        ])->findOrFail(decrypt($id));

        $activityLogs = UserActivityLog::where('user_id', $user->id)->latest('occurred_at')->limit(50)->get();
        $deviceSessions = UserDeviceSession::where('user_id', $user->id)->latest('last_used_at')->limit(20)->get();
        $shortlists = Shortlist::with(['user.member', 'user.addresses.city', 'user.addresses.state', 'user.addresses.country'])->where('shortlisted_by', $user->id)->latest()->limit(25)->get();
        $sentProposals = ExpressInterest::with(['recipient.member', 'recipient.addresses.city'])->where('interested_by', $user->id)->latest()->limit(25)->get();
        $receivedProposals = ExpressInterest::with(['sender.member', 'sender.addresses.city'])->where('user_id', $user->id)->latest()->limit(25)->get();
        $packagePayments = PackagePayment::with('package')->where('user_id', $user->id)->latest()->limit(10)->get();
        $profileViewsMade = ProfileViewer::with(['user.member'])->where('viewed_by', $user->id)->latest()->limit(20)->get();
        $profileViewsReceived = ProfileViewer::with(['profileViewer.member'])->where('user_id', $user->id)->latest()->limit(20)->get();
        $matches = ProfileMatch::with('matchedUser.member')->where('user_id', $user->id)->latest('calculated_at')->limit(15)->get();

        $summary = $this->summary($user, $activityLogs, $deviceSessions);
        $journey = $this->journey($user, $summary, $shortlists, $sentProposals, $receivedProposals, $matches);
        $diagnostics = $this->diagnostics($user, $summary, $matches);

        return view('admin.members.activity', compact('user', 'activityLogs', 'deviceSessions', 'shortlists', 'sentProposals', 'receivedProposals', 'packagePayments', 'profileViewsMade', 'profileViewsReceived', 'matches', 'summary', 'journey', 'diagnostics'));
    }

    private function summary(User $user, Collection $activityLogs, Collection $deviceSessions): array
    {
        $lastLogin = $activityLogs->where('event_type', 'login')->first()?->occurred_at ?? $deviceSessions->max('last_used_at');
        $lastSeen = $this->lastSeenAt($user, $activityLogs, $deviceSessions);
        $sessionCount = UserActivityLog::where('user_id', $user->id)->where('event_type', 'login')->count() + UserDeviceSession::where('user_id', $user->id)->count();

        return [
            'registered_at' => $user->created_at,
            'last_login_at' => $lastLogin,
            'last_seen_at' => $lastSeen,
            'days_since_last_login' => $lastLogin ? Carbon::parse($lastLogin)->diffInDays(now()) : null,
            'days_since_last_activity' => $lastSeen ? Carbon::parse($lastSeen)->diffInDays(now()) : null,
            'session_count' => $sessionCount,
            'active_status' => $this->activeStatus($user, $lastSeen),
            'latest_location' => $activityLogs->firstWhere('location')?->location ?? $deviceSessions->firstWhere('ip_address')?->ip_address ?? $user->ip_address ?? null,
            'latest_ip' => $activityLogs->firstWhere('ip_address')?->ip_address ?? $deviceSessions->firstWhere('ip_address')?->ip_address ?? $user->ip_address ?? null,
            'verification_status' => $this->verificationStatus($user),
            'trust_badge_status' => $user->approved ? 'Allocated' : 'Not allocated',
            'package_name' => $user->member?->package?->name ?? 'No active package',
            'package_valid_until' => $user->member?->package_validity,
        ];
    }

    private function journey(User $user, array $summary, Collection $shortlists, Collection $sent, Collection $received, Collection $matches): array
    {
        return [
            ['label' => 'Registration', 'status' => 'complete', 'detail' => optional($user->created_at)->format('d M Y, h:i A')],
            ['label' => 'Verification', 'status' => $user->approved ? 'complete' : 'pending', 'detail' => $this->verificationStatus($user)],
            ['label' => 'Trust Badge', 'status' => $user->approved ? 'complete' : 'pending', 'detail' => $user->approved ? 'Allocated' : 'Waiting approval'],
            ['label' => 'Plan', 'status' => $user->member?->current_package_id ? 'complete' : 'pending', 'detail' => $user->member?->package?->name ?? 'No package'],
            ['label' => 'Login/Sessions', 'status' => ((int) ($summary['session_count'] ?? 0) > 0 || ! empty($summary['last_seen_at'])) ? 'complete' : 'pending', 'detail' => ((int) ($summary['session_count'] ?? 0)) . ' session(s) tracked'],
            ['label' => 'Discovery', 'status' => $matches->count() || ProfileViewer::where('viewed_by', $user->id)->exists() ? 'complete' : 'pending', 'detail' => $matches->count() . ' recommended matches'],
            ['label' => 'Shortlisting', 'status' => $shortlists->count() ? 'complete' : 'pending', 'detail' => $shortlists->count() . ' profiles shortlisted'],
            ['label' => 'Requests/Proposals', 'status' => ($sent->count() || $received->count()) ? 'complete' : 'pending', 'detail' => $sent->count() . ' sent / ' . $received->count() . ' received'],
            ['label' => 'Match', 'status' => ($sent->where('status.value', 'accepted')->count() || $received->where('status.value', 'accepted')->count()) ? 'complete' : 'pending', 'detail' => 'Accepted proposal check'],
        ];
    }

    private function diagnostics(User $user, array $summary, Collection $matches): array
    {
        $issues = [];
        $oppositeGenderCount = $this->eligibleOppositeGenderCount($user);

        if (! $user->approved) {
            $issues[] = ['level' => 'danger', 'title' => 'Profile approval pending', 'detail' => 'User may not receive normal discovery results until approved.'];
        }

        if (($summary['days_since_last_activity'] ?? 999) >= 14) {
            $issues[] = ['level' => 'warning', 'title' => 'User looks inactive', 'detail' => 'Last activity is more than 14 days old.'];
        }

        if ($oppositeGenderCount === 0) {
            $issues[] = ['level' => 'danger', 'title' => 'No eligible profiles found', 'detail' => 'No active approved opposite-gender profiles are currently available.'];
        } elseif ($matches->isEmpty()) {
            $issues[] = ['level' => 'warning', 'title' => 'No calculated matches yet', 'detail' => 'Eligible profiles exist, but match calculation/recommendation may need refresh.'];
        }

        if ($user->partner_expectations && $oppositeGenderCount > 0 && $matches->isEmpty()) {
            $issues[] = ['level' => 'info', 'title' => 'Preferences may be restrictive', 'detail' => 'Review age, location, religion/caste, education, and profession filters.'];
        }

        if (! $user->member?->current_package_id) {
            $issues[] = ['level' => 'warning', 'title' => 'No active package', 'detail' => 'Plan limits can restrict profile views, shortlists, and requests.'];
        }

        if ($issues === []) {
            $issues[] = ['level' => 'success', 'title' => 'No obvious blocker detected', 'detail' => 'User has baseline eligibility and activity data.'];
        }

        return [
            'eligible_profile_count' => $oppositeGenderCount,
            'sent_request_count' => ExpressInterest::where('interested_by', $user->id)->count(),
            'shortlist_count' => Shortlist::where('shortlisted_by', $user->id)->count(),
            'match_count' => ProfileMatch::where('user_id', $user->id)->count(),
            'issues' => $issues,
        ];
    }

    private function lastSeenAt(User $user, Collection $activityLogs, Collection $deviceSessions): ?Carbon
    {
        return collect([
            $user->last_activity ? Carbon::createFromTimestamp((int) $user->last_activity) : null,
            $activityLogs->max('occurred_at'),
            $deviceSessions->max('last_used_at'),
        ])->filter()->map(fn ($value) => Carbon::parse($value))->sortDesc()->first();
    }

    private function activeStatus(User $user, $lastSeen): string
    {
        if ((int) $user->blocked === 1) {
            return 'Blocked';
        }

        if ((int) $user->deactivated === 1) {
            return 'Deactivated';
        }

        if ($lastSeen && Carbon::parse($lastSeen)->greaterThanOrEqualTo(now()->subMinutes(15))) {
            return 'Online / recently active';
        }

        if ($lastSeen && Carbon::parse($lastSeen)->greaterThanOrEqualTo(now()->subDays(14))) {
            return 'Active';
        }

        return 'Inactive / dormant';
    }

    private function verificationStatus(User $user): string
    {
        if ((int) $user->approved === 1) {
            return 'Approved';
        }

        return $user->member?->ai_verification_status ?? $user->member?->verification_status ?? ($user->verification_info ? 'Submitted' : 'Not submitted');
    }

    private function eligibleOppositeGenderCount(User $user): int
    {
        $gender = $user->member?->gender;
        $opposite = $gender == 1 ? 2 : ($gender == 2 ? 1 : null);

        return User::where('user_type', 'member')
            ->where('id', '!=', $user->id)
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->when($opposite, fn ($query) => $query->whereHas('member', fn ($q) => $q->where('gender', $opposite)))
            ->count();
    }
}




