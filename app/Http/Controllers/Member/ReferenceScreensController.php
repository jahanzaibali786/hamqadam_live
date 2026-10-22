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

    public function blogs(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $blogs = Blog::active()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->with('category')
            ->latest()->paginate(9)->withQueryString();

        return view('frontend.member.reference.blogs', compact('blogs', 'search'));
    }

    public function stories(): View
    {
        $stories = HappyStory::query()->where('approved', 1)->latest()->paginate(9);
        return view('frontend.member.reference.stories', compact('stories'));
    }

    public function settings(Request $request): View
    {
        $member = $request->user()->member;
        return view('frontend.member.reference.settings', compact('member'));
    }
}
