<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Family;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Family\FamilyDecisionRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyApprovalRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyConversationRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyMessageRequest;
use App\Http\Requests\Api\V1\Family\StoreFamilyNoteRequest;
use App\Http\Requests\Api\V1\Family\StoreGuardianRequest;
use App\Http\Requests\Api\V1\Family\ToggleWaliModeRequest;
use App\Http\Requests\Api\V1\Family\UpdateGuardianPermissionsRequest;
use App\Http\Resources\Api\V1\Family\FamilyApprovalResource;
use App\Http\Resources\Api\V1\Family\FamilyGuardianResource;
use App\Http\Resources\Api\V1\Family\FamilyNoteResource;
use App\Services\Api\V1\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends ApiController
{
    public function __construct(private readonly FamilyService $family)
    {
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
}
