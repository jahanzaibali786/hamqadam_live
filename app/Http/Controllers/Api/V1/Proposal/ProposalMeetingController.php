<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Proposal;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Proposal\StoreMeetingFeedbackRequest;
use App\Http\Requests\Api\V1\Proposal\StoreProposalMeetingRequest;
use App\Http\Requests\Api\V1\Proposal\StoreRelationshipStatusRequest;
use App\Http\Requests\Api\V1\Proposal\UpdateProposalMeetingRequest;
use App\Models\ExpressInterest;
use App\Models\FamilyGuardianLink;
use App\Models\ProposalEvent;
use App\Models\ProposalMeeting;
use App\Models\RelationshipStatusUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProposalMeetingController extends ApiController
{
    public function index(Request $request, int $proposal): JsonResponse
    {
        $proposalModel = $this->proposalForActor($request, $proposal);

        return $this->success(
            $proposalModel->meetings()->with(['organizer:id,first_name,last_name', 'chaperone:id,first_name,last_name'])->latest()->paginate(20),
            'Proposal meetings fetched successfully.'
        );
    }

    public function store(StoreProposalMeetingRequest $request, int $proposal): JsonResponse
    {
        $proposalModel = $this->proposalForActor($request, $proposal);

        $meeting = DB::transaction(function () use ($request, $proposalModel) {
            $data = $request->validated();
            $meeting = ProposalMeeting::create([
                'express_interest_id' => $proposalModel->id,
                'organizer_user_id' => $request->user()->id,
                'meeting_type' => $data['meeting_type'],
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'meeting_url' => $data['meeting_url'] ?? null,
                'location' => $data['location'] ?? null,
                'chaperone_mode' => (bool) ($data['chaperone_mode'] ?? false),
                'chaperone_user_id' => $data['chaperone_user_id'] ?? null,
                'recording_consent_requested' => (bool) ($data['recording_consent_requested'] ?? false),
                'recording_consent_status' => ! empty($data['recording_consent_requested']) ? 'pending' : 'not_requested',
                'pre_meeting_questionnaire' => $data['pre_meeting_questionnaire'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ProposalEvent::create([
                'express_interest_id' => $proposalModel->id,
                'actor_id' => $request->user()->id,
                'event' => 'meeting_scheduled',
                'metadata' => ['meeting_id' => $meeting->id, 'meeting_type' => $meeting->meeting_type],
            ]);

            return $meeting->load(['organizer:id,first_name,last_name', 'chaperone:id,first_name,last_name']);
        });

        return $this->success($meeting, 'Meeting scheduled successfully.', 201);
    }

    public function update(UpdateProposalMeetingRequest $request, int $meeting): JsonResponse
    {
        $meetingModel = ProposalMeeting::with('proposal')->findOrFail($meeting);
        $this->ensureActorCanAccessProposal($request, $meetingModel->proposal);

        $meetingModel->fill($request->validated())->save();

        return $this->success($meetingModel->fresh(['organizer:id,first_name,last_name', 'chaperone:id,first_name,last_name']), 'Meeting updated successfully.');
    }

    public function feedback(StoreMeetingFeedbackRequest $request, int $meeting): JsonResponse
    {
        $meetingModel = ProposalMeeting::with('proposal')->findOrFail($meeting);
        $this->ensureActorCanAccessProposal($request, $meetingModel->proposal);

        $feedback = $meetingModel->post_meeting_feedback ?: [];
        $feedback[(string) $request->user()->id] = $request->validated();
        $meetingModel->forceFill(['post_meeting_feedback' => $feedback])->save();

        ProposalEvent::create([
            'express_interest_id' => $meetingModel->express_interest_id,
            'actor_id' => $request->user()->id,
            'event' => 'meeting_feedback_added',
            'metadata' => ['meeting_id' => $meetingModel->id],
        ]);

        return $this->success($meetingModel->fresh(), 'Meeting feedback saved successfully.');
    }

    public function recordingConsent(Request $request, int $meeting): JsonResponse
    {
        $data = $request->validate([
            'consent' => ['required', 'boolean'],
            'recording_url' => ['sometimes', 'nullable', 'url', 'max:500'],
        ]);

        $meetingModel = ProposalMeeting::with('proposal')->findOrFail($meeting);
        $this->ensureActorCanAccessProposal($request, $meetingModel->proposal);

        $meetingModel->forceFill([
            'recording_consent_requested' => true,
            'recording_consent_status' => $data['consent'] ? 'approved' : 'rejected',
            'recording_url' => $data['recording_url'] ?? $meetingModel->recording_url,
        ])->save();

        return $this->success($meetingModel->fresh(), 'Recording consent updated successfully.');
    }

    public function relationshipStatus(StoreRelationshipStatusRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->user()->id === (int) $data['partner_user_id']) {
            throw new ApiException('Partner must be a different user.', 422, ApiErrorCode::ValidationFailed->value);
        }

        if (! empty($data['proposal_id'])) {
            $this->proposalForActor($request, (int) $data['proposal_id']);
        }

        $status = RelationshipStatusUpdate::create([
            'user_id' => $request->user()->id,
            'partner_user_id' => $data['partner_user_id'],
            'express_interest_id' => $data['proposal_id'] ?? null,
            'status' => $data['status'],
            'status_date' => $data['status_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_public' => (bool) ($data['is_public'] ?? false),
            'moderation_status' => 'pending',
        ])->load(['partner:id,first_name,last_name', 'proposal']);

        return $this->success($status, 'Relationship status submitted for review.', 201);
    }

    private function proposalForActor(Request $request, int $proposalId): ExpressInterest
    {
        $proposal = ExpressInterest::with(['sender', 'recipient'])->findOrFail($proposalId);
        $this->ensureActorCanAccessProposal($request, $proposal);

        return $proposal;
    }

    private function ensureActorCanAccessProposal(Request $request, ExpressInterest $proposal): void
    {
        $actorId = (int) $request->user()->id;
        if (in_array($actorId, [(int) $proposal->interested_by, (int) $proposal->user_id], true)) {
            return;
        }

        $isGuardian = FamilyGuardianLink::where('guardian_user_id', $actorId)
            ->where('status', 'approved')
            ->whereIn('profile_user_id', [(int) $proposal->interested_by, (int) $proposal->user_id])
            ->exists();

        if (! $isGuardian) {
            throw new ApiException('Proposal access denied.', 403, ApiErrorCode::Forbidden->value);
        }
    }
}
