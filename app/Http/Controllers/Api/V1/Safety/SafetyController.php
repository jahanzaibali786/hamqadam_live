<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Safety;

use App\Enums\SafetyActionType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Safety\ModerationQueueRequest;
use App\Http\Requests\Api\V1\Safety\ReportUserRequest;
use App\Http\Requests\Api\V1\Safety\ResolveModerationCaseRequest;
use App\Http\Requests\Api\V1\Safety\SafetyActionRequest;
use App\Http\Resources\Api\V1\Safety\ModerationCaseResource;
use App\Services\Api\V1\Safety\SafetyService;
use Illuminate\Http\JsonResponse;

class SafetyController extends ApiController
{
    public function __construct(private readonly SafetyService $safety)
    {
    }

    public function report(ReportUserRequest $request): JsonResponse
    {
        return $this->success(
            new ModerationCaseResource($this->safety->report($request->user(), $request->validated())),
            'Report submitted successfully.',
            201
        );
    }

    public function block(SafetyActionRequest $request): JsonResponse
    {
        $this->safety->action($request->user(), $request->validated(), SafetyActionType::Block);

        return $this->success(message: 'User blocked successfully.');
    }

    public function mute(SafetyActionRequest $request): JsonResponse
    {
        $this->safety->action($request->user(), $request->validated(), SafetyActionType::Mute);

        return $this->success(message: 'User muted successfully.');
    }

    public function restrict(SafetyActionRequest $request): JsonResponse
    {
        $this->safety->action($request->user(), $request->validated(), SafetyActionType::Restrict);

        return $this->success(message: 'User restricted successfully.');
    }

    public function queue(ModerationQueueRequest $request): JsonResponse
    {
        return ModerationCaseResource::collection($this->safety->queue($request->user(), $request->validated()))
            ->additional(['success' => true])
            ->response();
    }

    public function resolve(ResolveModerationCaseRequest $request, int $case): JsonResponse
    {
        return $this->success(
            new ModerationCaseResource($this->safety->resolve($request->user(), $case, $request->validated())),
            'Moderation case updated.'
        );
    }
}
