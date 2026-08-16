<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Verification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Verification\RejectVerificationRequest;
use App\Http\Requests\Api\V1\Verification\SubmitVerificationRequest;
use App\Http\Requests\Api\V1\Verification\VerificationQueueRequest;
use App\Http\Resources\Api\V1\Verification\VerificationRequestResource;
use App\Services\Api\V1\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends ApiController
{
    public function __construct(private readonly VerificationService $verification)
    {
    }

    public function current(Request $request): JsonResponse
    {
        $verificationRequest = $this->verification->current($request->user());

        return $this->success($verificationRequest ? new VerificationRequestResource($verificationRequest) : null);
    }

    public function history(Request $request): JsonResponse
    {
        return VerificationRequestResource::collection($this->verification->history($request->user()))
            ->additional(['success' => true])
            ->response();
    }

    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        $verificationRequest = $this->verification->submit($request->user(), $request->validated());

        return $this->success(new VerificationRequestResource($verificationRequest), 'Verification request submitted successfully.', 201);
    }

    public function queue(VerificationQueueRequest $request): JsonResponse
    {
        return VerificationRequestResource::collection(
            $this->verification->queue($request->user(), $request->validated())
        )->additional(['success' => true])->response();
    }

    public function show(Request $request, int $verification): JsonResponse
    {
        return $this->success(new VerificationRequestResource(
            $this->verification->showForModerator($request->user(), $verification)
        ));
    }

    public function approve(Request $request, int $verification): JsonResponse
    {
        return $this->success(
            new VerificationRequestResource($this->verification->approve($request->user(), $verification)),
            'Verification request approved successfully.'
        );
    }

    public function reject(RejectVerificationRequest $request, int $verification): JsonResponse
    {
        return $this->success(
            new VerificationRequestResource($this->verification->reject($request->user(), $verification, $request->validated('reason'))),
            'Verification request rejected successfully.'
        );
    }
}
