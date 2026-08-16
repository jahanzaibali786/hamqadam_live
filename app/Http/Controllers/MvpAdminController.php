<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommunityForum;
use App\Models\CommunityPost;
use App\Models\CommunityThread;
use App\Models\ExpertQuestion;
use App\Models\MarriageTip;
use App\Models\PackagePayment;
use App\Models\RegionalUpdate;
use App\Models\RelationshipStatusUpdate;
use App\Models\Webinar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MvpAdminController extends Controller
{
    public function payments(Request $request)
    {
        $query = PackagePayment::with(['user', 'package'])->latest();

        if ($request->filled('status')) {
            $query->where('payment_status', $request->string('status'));
        }

        if ($request->filled('gateway')) {
            $query->where('payment_method', $request->string('gateway'));
        }

        $payments = $query->paginate(20)->withQueryString();

        return view('admin.mvp.payments', compact('payments'));
    }

    public function rejectPayment(Request $request, int $payment): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        PackagePayment::whereKey($payment)->update([
            'payment_status' => 'Failed',
            'gateway_status' => 'rejected',
            'payment_details' => json_encode([
                'admin_rejected_at' => now()->toISOString(),
                'reason' => $data['reason'] ?? null,
            ]),
        ]);

        flash(translate('Payment rejected successfully.'))->success();

        return back();
    }

    public function relationshipStatuses()
    {
        $statuses = RelationshipStatusUpdate::with(['user', 'partner', 'proposal'])
            ->latest()
            ->paginate(20);

        return view('admin.mvp.relationship_statuses', compact('statuses'));
    }

    public function updateRelationshipStatus(Request $request, int $status): RedirectResponse
    {
        $data = $request->validate([
            'moderation_status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        RelationshipStatusUpdate::whereKey($status)->update($data);

        flash(translate('Relationship status updated successfully.'))->success();

        return back();
    }

    public function proposalMeetings()
    {
        $meetings = \App\Models\ProposalMeeting::with(['proposal.sender', 'proposal.recipient', 'organizer', 'chaperone'])
            ->latest()
            ->paginate(20);

        return view('admin.mvp.proposal_meetings', compact('meetings'));
    }

    public function updateProposalMeeting(Request $request, int $meeting): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:scheduled,completed,cancelled,rescheduled'],
        ]);

        \App\Models\ProposalMeeting::whereKey($meeting)->update($data);

        flash(translate('Meeting status updated successfully.'))->success();

        return back();
    }

    public function expertQuestions()
    {
        $questions = ExpertQuestion::with('user')->latest()->paginate(20);

        return view('admin.mvp.expert_questions', compact('questions'));
    }

    public function answerExpertQuestion(Request $request, int $question): RedirectResponse
    {
        $data = $request->validate([
            'answer' => ['required', 'string'],
            'expert_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:pending,answered,rejected'],
        ]);

        ExpertQuestion::whereKey($question)->update($data);

        flash(translate('Expert question updated successfully.'))->success();

        return back();
    }

    public function community()
    {
        $threads = CommunityThread::with(['forum', 'user'])->latest()->paginate(15, ['*'], 'threads_page');
        $posts = CommunityPost::with(['thread', 'user'])->latest()->paginate(15, ['*'], 'posts_page');

        return view('admin.mvp.community', compact('threads', 'posts'));
    }

    public function updateThread(Request $request, int $thread): RedirectResponse
    {
        $data = $request->validate([
            'moderation_status' => ['required', 'string', 'in:pending,approved,rejected'],
            'is_locked' => ['sometimes', 'boolean'],
        ]);

        $data['is_locked'] = $request->boolean('is_locked');
        CommunityThread::whereKey($thread)->update($data);

        flash(translate('Thread updated successfully.'))->success();

        return back();
    }

    public function updatePost(Request $request, int $post): RedirectResponse
    {
        $data = $request->validate([
            'moderation_status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        CommunityPost::whereKey($post)->update($data);

        flash(translate('Post updated successfully.'))->success();

        return back();
    }

    public function content()
    {
        return view('admin.mvp.content', [
            'forums' => CommunityForum::latest()->paginate(10, ['*'], 'forums_page'),
            'webinars' => Webinar::latest()->paginate(10, ['*'], 'webinars_page'),
            'tips' => MarriageTip::latest()->paginate(10, ['*'], 'tips_page'),
            'updates' => RegionalUpdate::latest()->paginate(10, ['*'], 'updates_page'),
        ]);
    }

    public function storeForum(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        CommunityForum::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(5)),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        flash(translate('Forum created successfully.'))->success();

        return back();
    }

    public function storeWebinar(Request $request): RedirectResponse
    {
        Webinar::create($request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'host_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'meeting_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'status' => ['required', 'string', 'in:scheduled,live,completed,cancelled'],
        ]));

        flash(translate('Webinar created successfully.'))->success();

        return back();
    }

    public function storeMarriageTip(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'publish_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        MarriageTip::create($data);

        flash(translate('Marriage tip created successfully.'))->success();

        return back();
    }

    public function storeRegionalUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'publish_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        RegionalUpdate::create($data);

        flash(translate('Regional update created successfully.'))->success();

        return back();
    }
}
