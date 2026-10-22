<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\AiFeatureRequest;
use App\Models\Blog;
use App\Models\ExpressInterest;
use App\Models\FamilyGuardianLink;
use App\Models\HappyStory;
use App\Models\ProfileMatch;
use App\Models\ProfilePrivacySetting;
use App\Models\NotificationPreference;
use App\Models\Package;
use App\Models\ProposalEvent;
use App\Models\ProposalMeeting;
use App\Services\Api\V1\Ai\AiFeatureService;
use App\Support\StoryPublicId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ReferenceScreensController extends Controller
{
    public function meetings(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $proposals = ExpressInterest::query()
            ->where('status', ProposalStatus::Accepted->value)
            ->where(fn ($q) => $q->where('interested_by', $userId)->orWhere('user_id', $userId))
            ->with(['sender:id,first_name,last_name,photo', 'recipient:id,first_name,last_name,photo'])
            ->latest()->get();

        $meetings = ProposalMeeting::query()
            ->whereHas('proposal', fn ($q) => $q->where('interested_by', $userId)->orWhere('user_id', $userId))
            ->with('proposal.sender:id,first_name,last_name')
            ->with('proposal.recipient:id,first_name,last_name')
            ->orderByDesc('scheduled_at')->limit(20)->get();

        return view('frontend.member.reference.meetings', compact('proposals', 'meetings'));
    }

    public function storeMeeting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'proposal_id' => ['required', 'integer', 'exists:express_interests,id'],
            'meeting_type' => ['required', 'in:virtual,family,in_person'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:180'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'chaperone_mode' => ['nullable', 'boolean'],
        ]);

        $actorId = (int) $request->user()->id;
        $proposal = ExpressInterest::query()
            ->where('status', ProposalStatus::Accepted->value)
            ->where(fn ($q) => $q->where('interested_by', $actorId)->orWhere('user_id', $actorId))
            ->findOrFail($data['proposal_id']);

        if ($data['meeting_type'] === 'in_person' && empty($data['location'])) {
            return back()->withErrors(['location' => 'Provide a public meeting location.'])->withInput();
        }

        $chaperoneRequested = $request->boolean('chaperone_mode') || $data['meeting_type'] === 'family';
        $guardianId = null;
        if ($chaperoneRequested) {
            $guardianId = FamilyGuardianLink::query()
                ->where('profile_user_id', $actorId)
                ->where('status', 'approved')
                ->whereNull('revoked_at')
                ->whereNull('paused_at')
                ->value('guardian_user_id');
            if (! $guardianId) {
                return back()->withErrors(['chaperone_mode' => 'An approved guardian is required for this meeting option.'])->withInput();
            }
        }

        DB::transaction(function () use ($proposal, $data, $actorId, $chaperoneRequested, $guardianId): void {
            $meeting = ProposalMeeting::create([
                'express_interest_id' => $proposal->id,
                'organizer_user_id' => $actorId,
                'meeting_type' => $data['meeting_type'],
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'],
                'meeting_url' => $data['meeting_url'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'chaperone_mode' => $chaperoneRequested,
                'chaperone_user_id' => $guardianId,
                'recording_consent_requested' => false,
                'recording_consent_status' => 'not_requested',
            ]);
            ProposalEvent::create([
                'express_interest_id' => $proposal->id,
                'actor_id' => $actorId,
                'event' => 'meeting_scheduled',
                'metadata' => ['meeting_id' => $meeting->id, 'meeting_type' => $meeting->meeting_type],
            ]);
        });

        return redirect()->route('member.reference.meetings')
            ->with('success', 'Your meeting has been recorded. Coordinate the invitation and meeting details with the other participant.');
    }

    public function packages(Request $request): View
    {
        $packages = Package::query()->orderBy('price')->get();
        $member = $request->user()->member;
        return view('frontend.member.reference.packages', compact('packages', 'member'));
    }

    public function assistant(Request $request): View
    {
        $history = AiFeatureRequest::query()->where('user_id', $request->user()->id)
            ->whereIn('feature', ['bio_generator', 'conversation_starters', 'profile_quality_checker', 'scam_detection', 'red_flag_detection'])
            ->latest()->limit(12)->get();
        return view('frontend.member.reference.assistant', compact('history'));
    }

    public function assistantAction(Request $request, AiFeatureService $ai): RedirectResponse
    {
        $data = $request->validate([
            'tool' => ['required', 'in:bio,starters,quality,scam,red_flags'],
            'text' => ['nullable', 'string', 'max:5000'],
        ]);
        $input = ['text' => $data['text'] ?? ''];
        $user = $request->user();
        $result = match ($data['tool']) {
            'bio' => $ai->bio($user, $input),
            'starters' => $ai->conversationStarters($user, $input),
            'quality' => $ai->profileQuality($user, $input),
            'scam' => $ai->safetyScan($user, 'scam_detection', $input),
            'red_flags' => $ai->safetyScan($user, 'red_flag_detection', $input),
        };

        return redirect()->route('member.reference.assistant')->with('result_id', (int) $result->id);
    }

    public function blogDetail(string $slug): View
    {
        $blog = Blog::active()->with('category')->where('slug', $slug)->firstOrFail();
        return view('frontend.member.reference.blog_details', compact('blog'));
    }

    public function blogs(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $categories = \App\Models\BlogCategory::query()->orderBy('category_name')->get();
        $query = Blog::active()->with('category')
            ->when($search !== '', fn ($q) => $q->where(fn ($nested) =>
                $nested->where('title', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('short_description', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->when($category !== '', fn ($q) => $q->whereHas('category',
                fn ($cat) => $cat->where('slug', $category)));
        $featured = ($search === '' && $category === '') ? Blog::active()->with('category')->latest()->first() : null;
        $popular = Blog::active()->latest()->limit(5)->get();
        $blogs = $query->latest()->paginate(6)->withQueryString();
        return view('frontend.member.reference.blogs', compact('blogs', 'search', 'category', 'categories', 'featured', 'popular'));
    }

    public function storyDetail(string $token): View
    {
        $id = StoryPublicId::decode($token);
        abort_unless($id, 404);
        $story = HappyStory::query()->where('approved', 1)->findOrFail($id);
        return view('frontend.member.reference.story_details', compact('story'));
    }

    public function stories(Request $request): View
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:marriage,engagement'],
            'city' => ['nullable', 'string', 'max:80'],
            'profession' => ['nullable', 'string', 'max:80'],
            'period' => ['nullable', 'in:all,year,6months'],
        ]);
        $base = HappyStory::query()->where('approved', 1);
        $totalStories = (clone $base)->count();
        $stories = $base
            ->when(isset($data['type']), function ($q) use ($data) {
                $term = $data['type'] === 'marriage' ? 'marri' : 'engag';
                $q->where(fn($x) => $x->where('title', 'like', '%'.$term.'%')
                    ->orWhere('details', 'like', '%'.$term.'%'));
            })
            ->when(filled($data['city'] ?? null), fn($q) => $q->whereHas('user.addresses',
                fn($x) => $x->whereHas('city', fn($city) =>
                    $city->where('name', 'like', '%'.addcslashes($data['city'], '%_\\\\').'%'))))
            ->when(filled($data['profession'] ?? null), fn($q) => $q->whereHas('user.member',
                fn($x) => $x->where('job_title', 'like', '%'.addcslashes($data['profession'], '%_\\\\').'%')))
            ->when(($data['period'] ?? 'all') === 'year', fn($q) => $q->where('created_at', '>=', now()->subYear()))
            ->when(($data['period'] ?? 'all') === '6months', fn($q) => $q->where('created_at', '>=', now()->subMonths(6)))
            ->with(['user.member', 'user.addresses.city'])
            ->latest()->paginate(6)->withQueryString();
        return view('frontend.member.reference.stories', compact('stories', 'totalStories'));
    }

    public function home(Request $request): View
    {
        $viewer = $request->user();
        $recommended = ProfileMatch::query()
            ->where('user_id', $viewer->id)
            ->where('match_id', '!=', $viewer->id)
            ->where('match_percentage', '>', 0)
            ->whereHas('matchedUser', fn ($q) => $q->where('user_type', 'member')
                ->where('approved', 1)->where('blocked', 0)->where('deactivated', 0)
                ->whereHas('member', fn ($m) => $m->where(fn ($v) =>
                    $v->where('hide_profile', 0)->orWhereNull('hide_profile'))))
            ->with(['matchedUser.member', 'matchedUser.addresses.city', 'matchedUser.education'])
            ->orderByDesc('match_percentage')->limit(4)->get();

        $completion = max(0, min(100, (int) ($viewer->member?->profile_completion_percentage ?? 0)));

        return view('frontend.member.reference.home', compact('recommended', 'completion'));
    }

    public function settings(Request $request): View
    {
        $user = $request->user();
        $member = $user->member;
        $privacy = ProfilePrivacySetting::firstOrNew(['user_id' => $user->id]);
        $notifications = NotificationPreference::firstOrNew(['user_id' => $user->id]);
        $address = $user->addresses()->where('type', 'present')->with('city','country')->first();
        return view('frontend.member.reference.settings',
            compact('member', 'privacy', 'notifications', 'address'));
    }

    public function savePrivacy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate([
            'hide_profile' => ['nullable', 'boolean'],
            'show_photo' => ['nullable', 'boolean'],
            'show_location' => ['nullable', 'boolean'],
            'allow_profile_view_notifications' => ['nullable', 'boolean'],
            'do_not_disturb' => ['nullable', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $user): void {
            ProfilePrivacySetting::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'show_photo' => $request->boolean('show_photo'),
                    'show_location' => $request->boolean('show_location'),
                    'allow_profile_view_notifications' => $request->boolean('allow_profile_view_notifications'),
                    'do_not_disturb' => $request->boolean('do_not_disturb'),
                ]
            );
            if ($user->member) {
                $user->member->update(['hide_profile' => $request->boolean('hide_profile')]);
            }
        });
        return redirect()->route('member.reference.settings', ['section' => 'privacy'])
            ->with('success', 'Your profile privacy preferences have been saved.');
    }

    public function saveNotificationSummary(Request $request): RedirectResponse
    {
        $request->validate([
            'push_enabled' => ['nullable','boolean'],
            'email_enabled' => ['nullable','boolean'],
            'engagement_enabled' => ['nullable','boolean'],
        ]);
        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'push_enabled' => $request->boolean('push_enabled'),
                'email_enabled' => $request->boolean('email_enabled'),
                'engagement_enabled' => $request->boolean('engagement_enabled'),
            ]
        );
        return redirect()->route('member.reference.settings', ['section' => 'notifications'])
            ->with('success', 'Notification preferences saved.');
    }
}
