<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Family;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Family\FamilyDecisionRequest;
use App\Http\Requests\Api\V1\Family\FamilyIntroductionRequest;
use App\Http\Requests\Api\V1\Family\GuardianActionRequest;
use App\Http\Requests\Api\V1\Family\StoreGuardianInvitationRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyApprovalRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyConversationRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyMessageRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyNoteRequest;
use App\Http\Requests\Api\V1\Family\StoreGuardianRequest;
use App\Http\Requests\Api\V1\Family\ToggleWaliModeRequest;
use App\Http\Requests\Api\V1\Family\UpdateGuardianPermissionsRequest;
use App\Enums\GuardianPermission;
use App\Http\Resources\Api\V1\Family\FamilyApprovalResource;
use App\Http\Resources\Api\V1\Family\FamilyGuardianResource;
use App\Http\Resources\Api\V1\Family\FamilyIntroductionResource;
use App\Http\Resources\Api\V1\Family\FamilyNoteResource;
use App\Http\Resources\Api\V1\Family\GuardianActivityResource;
use App\Http\Resources\Api\V1\Family\GuardianInvitationResource;
use App\Services\Api\V1\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends ApiController
{
    public function __construct(
        private readonly FamilyService $family,
        private readonly \App\Services\Api\V1\Family\GuardianModeService $guardianMode,
        private readonly \App\Services\Api\V1\Family\GuardianAuthService $guardianAuth,
    ) {
    }

    public function guardians(Request $request): JsonResponse
    {
        return FamilyGuardianResource::collection($this->family->guardians($request->user()))
            ->additional(['success' => true])->response();
    }

    public function managedProfiles(Request $request): JsonResponse
    {
        return FamilyGuardianResource::collection($this->family->managedProfiles($request->user()))
            ->additional(['success' => true])->response();
    }

    public function storeGuardian(StoreGuardianRequest $request): JsonResponse
    {
        return $this->success(
            new FamilyGuardianResource($this->family->storeGuardian($request->user(), $request->validated())),
            'Guardian invitation created.',
            201
        );
    }

    public function approveGuardian(Request $request, int $guardian): JsonResponse
    {
        return $this->success(new FamilyGuardianResource($this->family->approveGuardian($request->user(), $guardian)), 'Guardian link approved.');
    }

    public function revokeGuardian(Request $request, int $guardian): JsonResponse
    {
        $this->family->revokeGuardian($request->user(), $guardian);

        return $this->success(message: 'Guardian link removed.');
    }

    public function approvalRequests(Request $request): JsonResponse
    {
        return FamilyApprovalResource::collection($this->family->approvalRequests($request->user()))
            ->additional(['success' => true])->response();
    }

    public function requestApproval(StoreFamilyApprovalRequest $request): JsonResponse
    {
        return $this->success(
            new FamilyApprovalResource($this->family->requestApproval($request->user(), $request->validated())),
            'Family approval request created.',
            201
        );
    }

    public function approveRequest(FamilyDecisionRequest $request, int $approval): JsonResponse
    {
        return $this->success(new FamilyApprovalResource(
            $this->family->decideApproval($request->user(), $approval, 'approved', $request->validated('note'))
        ), 'Family approval request approved.');
    }

    public function rejectRequest(FamilyDecisionRequest $request, int $approval): JsonResponse
    {
        return $this->success(new FamilyApprovalResource(
            $this->family->decideApproval($request->user(), $approval, 'rejected', $request->validated('note'))
        ), 'Family approval request rejected.');
    }

    public function notes(Request $request, int $profile): JsonResponse
    {
        return FamilyNoteResource::collection($this->family->notes($request->user(), $profile))
            ->additional(['success' => true])->response();
    }

    public function storeNote(StoreFamilyNoteRequest $request): JsonResponse
    {
        return $this->success(
            new FamilyNoteResource($this->family->storeNote($request->user(), $request->validated())),
            'Family note added.',
            201
        );
    }

    public function dashboard(Request $request): JsonResponse
    {
        return $this->success(
            $this->family->dashboard($request->user(), $request->integer('profile_user_id') ?: null),
            'Family dashboard fetched successfully.'
        );
    }

    public function updateGuardian(UpdateGuardianPermissionsRequest $request, int $guardian): JsonResponse
    {
        return $this->success(
            new FamilyGuardianResource($this->family->updateGuardian($request->user(), $guardian, $request->validated())),
            'Guardian settings updated.'
        );
    }

    public function waliMode(ToggleWaliModeRequest $request): JsonResponse
    {
        return $this->success($this->family->setWaliMode($request->user(), (bool) $request->validated('enabled')));
    }

    public function conversations(Request $request): JsonResponse
    {
        return $this->success($this->family->conversations($request->user()), 'Family conversations fetched successfully.');
    }

    public function startConversation(StoreFamilyConversationRequest $request): JsonResponse
    {
        return $this->success(
            $this->family->startConversation($request->user(), $request->validated()),
            'Family conversation started.',
            201
        );
    }

    public function messages(Request $request, int $conversation): JsonResponse
    {
        return $this->success($this->family->messages($request->user(), $conversation), 'Family messages fetched successfully.');
    }

    public function sendMessage(StoreFamilyMessageRequest $request, int $conversation): JsonResponse
    {
        return $this->success(
            $this->family->sendMessage($request->user(), $conversation, $request->validated()),
            'Family message sent.',
            201
        );
    }

    public function digestPreview(Request $request): JsonResponse
    {
        return $this->success($this->family->digestPreview($request->user()), 'Guardian digest preview fetched successfully.');
    }

    // ── Guardian Mode (spec §5–§25) ─────────────────────────────────────────

    /** GET /family/guardian-mode/status — mode flag + permission catalog + presets. */
    public function guardianModeStatus(Request $request): JsonResponse
    {
        return $this->success([
            'enabled' => (bool) ($request->user()->member?->wali_mode_enabled ?? false),
            'permissions' => GuardianPermission::catalog(),
            'presets' => [
                'view_only' => GuardianPermission::preset('view_only'),
                'review' => GuardianPermission::preset('review'),
                'participate' => GuardianPermission::preset('participate'),
                'custom' => [],
            ],
            'defaults' => GuardianPermission::preset('defaults'),
        ]);
    }

    /** POST /family/guardian-mode — activate / pause the whole Guardian Mode. */
    public function toggleGuardianMode(Request $request): JsonResponse
    {
        $enabled = (bool) $request->boolean('enabled');
        $member = $request->user()->member()->firstOrFail();
        $member->forceFill(['wali_mode_enabled' => $enabled])->save();

        return $this->success(['enabled' => $enabled], $enabled ? 'Guardian Mode enabled.' : 'Guardian Mode disabled.');
    }

    /** POST /family/guardian-invitations — single-use expiring invite (§6). */
    public function storeGuardianInvitation(StoreGuardianInvitationRequest $request): JsonResponse
    {
        return $this->success(
            new GuardianInvitationResource($this->guardianMode->invite($request->user(), $request->validated())),
            'Guardian invitation created.',
            201
        );
    }

    /** GET /family/guardian-invitations — the member's sent invitations. */
    public function guardianInvitations(Request $request): JsonResponse
    {
        return GuardianInvitationResource::collection(
            \App\Models\GuardianInvitation::where('profile_user_id', $request->user()->id)->latest()->paginate(20)
        )->additional(['success' => true])->response();
    }

    /** POST /family/guardian-invitations/accept — guardian consumes the token (one-time). */
    public function acceptGuardianInvitation(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:64']]);

        return $this->success(
            new FamilyGuardianResource($this->guardianMode->acceptInvitation($request->user(), (string) $request->input('token'))),
            'Guardian invitation accepted.'
        );
    }

    /** POST /family/guardians/{guardian}/pause — pause without deleting (§25). */
    public function pauseGuardian(Request $request, int $guardian): JsonResponse
    {
        return $this->success(
            new FamilyGuardianResource($this->guardianMode->pause($request->user(), $guardian)),
            'Guardian access paused.'
        );
    }

    /** POST /family/guardians/{guardian}/resume */
    public function resumeGuardian(Request $request, int $guardian): JsonResponse
    {
        return $this->success(
            new FamilyGuardianResource($this->guardianMode->resume($request->user(), $guardian)),
            'Guardian access resumed.'
        );
    }

    /** PATCH /family/guardians/{guardian}/permissions — granular permission keys (§7). */
    public function updateGuardianPermissions(Request $request, int $guardian): JsonResponse
    {
        $request->validate(['permissions' => ['required', 'array']]);
        $link = \App\Models\FamilyGuardianLink::where('profile_user_id', $request->user()->id)->findOrFail($guardian);

        $this->guardianAuth->syncPermissions($link, $request->input('permissions'), (int) $request->user()->id);

        \App\Models\GuardianActivityLog::record($link->id, $link->guardian_user_id, (int) $request->user()->id, 'guardian_permissions_changed', FamilyGuardianLink::class, $link->id, [
            'permissions' => $request->input('permissions'),
        ]);

        \App\Services\NotificationHelper::guardianEvent(
            \App\Models\User::find($link->guardian_user_id),
            'guardian_permissions_changed',
            'Permissions Updated',
            'Your guardian permissions were updated by the member.',
            (int) $request->user()->id,
            $link->id,
        );

        return $this->success(
            new FamilyGuardianResource($link->fresh(['profile', 'guardian'])),
            'Guardian permissions updated.'
        );
    }

    /** GET /family/{profile}/activity — readable audit trail (§20). */
    public function guardianActivity(Request $request, int $profile): JsonResponse
    {
        return GuardianActivityResource::collection(
            $this->guardianMode->activity($request->user(), $profile)
        )->additional(['success' => true])->response();
    }

    /** GET /guardian/matches — guardian's review feed for one managed profile (§10). */
    public function guardianMatches(Request $request): JsonResponse
    {
        $request->validate(['profile_user_id' => ['required', 'integer', 'exists:users,id']]);

        return $this->success(
            $this->guardianMode->recommendedMatches($request->user(), (int) $request->integer('profile_user_id')),
            'Guardian matches fetched successfully.'
        );
    }

    /** POST /guardian/matches/shortlist — shortlist on behalf of the member (§10). */
    public function guardianShortlist(GuardianActionRequest $request): JsonResponse
    {
        $feedback = $this->guardianMode->shortlistMatch(
            $request->user(),
            (int) $request->integer('profile_user_id'),
            (int) $request->integer('target_user_id')
        );

        return $this->success(['id' => $feedback->id, 'feedback_type' => $feedback->feedback_type], 'Profile shortlisted for the member.');
    }

    /** POST /guardian/matches/feedback — not-suitable / recommend (§11). */
    public function guardianFeedback(GuardianActionRequest $request): JsonResponse
    {
        $feedback = $this->guardianMode->feedback(
            $request->user(),
            (int) $request->integer('profile_user_id'),
            (int) $request->integer('target_user_id'),
            (string) $request->input('feedback_type', 'not_suitable'),
            $request->validated('reason'),
            $request->validated('comment'),
        );

        return $this->success(['id' => $feedback->id, 'feedback_type' => $feedback->feedback_type], 'Feedback saved.');
    }

    /** POST /guardian/matches/note — guardian note with explicit visibility (§11). */
    public function guardianNote(GuardianActionRequest $request): JsonResponse
    {
        $result = $this->guardianMode->note(
            $request->user(),
            (int) $request->integer('profile_user_id'),
            (int) $request->integer('target_user_id'),
            (string) $request->input('comment', ''),
            (string) $request->input('visibility', 'primary_and_guardian'),
        );

        return $this->success(['id' => $result['note']->id, 'visibility' => $result['visibility']], 'Note saved.');
    }

    /** POST /family-introductions — controlled family stage (§15). */
    public function storeIntroduction(FamilyIntroductionRequest $request): JsonResponse
    {
        return $this->success(
            new FamilyIntroductionResource($this->guardianMode->requestIntroduction(
                $request->user(),
                (int) $request->integer('proposal_id'),
                $request->validated('message')
            )),
            'Family introduction requested.',
            201
        );
    }

    /** GET /family-introductions — introductions involving the caller. */
    public function introductions(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = \App\Models\FamilyIntroduction::whereHas('proposal', function ($query) use ($user): void {
            $query->where('user_id', $user->id)->orWhere('interested_by', $user->id);
        })->orWhere('initiated_by', $user->id)
            ->with('proposal')
            ->latest()
            ->paginate(20);

        return FamilyIntroductionResource::collection($items)->additional(['success' => true])->response();
    }

    /** POST /family-introductions/{id}/respond — accept or decline (§15). */
    public function respondIntroduction(FamilyIntroductionRequest $request, int $introduction): JsonResponse
    {
        return $this->success(
            new FamilyIntroductionResource($this->guardianMode->respondIntroduction(
                $request->user(),
                $introduction,
                (bool) $request->boolean('accept', true)
            )),
            $request->boolean('accept', true) ? 'Family introduction accepted.' : 'Family introduction declined.'
        );
    }

    /** POST /family-introductions/{id}/cancel — initiating side cancels (§15). */
    public function cancelIntroduction(Request $request, int $introduction): JsonResponse
    {
        return $this->success(
            new FamilyIntroductionResource($this->guardianMode->cancelIntroduction($request->user(), $introduction)),
            'Family introduction cancelled.'
        );
    }
}
