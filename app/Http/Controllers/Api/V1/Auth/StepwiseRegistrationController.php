<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Dto\Auth\DeviceData;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Jobs\RunAiVerification;
use App\Models\AiVerificationAttempt;
use App\Services\Api\V1\Auth\StepwiseRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StepwiseRegistrationController extends ApiController
{
    public function __construct(private readonly StepwiseRegistrationService $registration)
    {
    }

    public function definitions(): JsonResponse
    {
        return $this->success([
            'total_steps' => StepwiseRegistrationService::TOTAL_STEPS,
            'steps' => array_values($this->registration->definitions()),
        ]);
    }

    public function step1(Request $request): JsonResponse
    {
        $token = $this->registration->start($request->all(), DeviceData::fromRequest($request));

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expiresAt->toISOString(),
            'user' => new UserResource($token->user->load('member')),
            'registration' => $this->registration->status($token->user),
        ], 'Registration step 1 saved.', 201);
    }

    public function save(Request $request, int $step): JsonResponse
    {
        if ($step < 2 || $step > StepwiseRegistrationService::TOTAL_STEPS) {
            return $this->error('Invalid registration step.', 404, 'invalid_registration_step');
        }

        $registration = $this->registration->saveStep($request->user(), $step, $request->all(), $request);

        $user = $request->user()->fresh('member');

        /*
         * Step 11 is the Photos step - it is the first point at which an image
         * exists on the account, so it is the earliest moment AI verification
         * can do anything useful. Fired after the response so the step never
         * waits on the model (see RunAiVerification for why afterResponse).
         */
        if ($step === StepwiseRegistrationService::TOTAL_STEPS) {
            RunAiVerification::dispatchAfterResponse(
                $user->id,
                AiVerificationAttempt::SOURCE_REGISTRATION_API
            );
        }

        $payload = [
            'user' => new UserResource($user),
            'registration' => $registration,
        ];

        if ($step === StepwiseRegistrationService::TOTAL_STEPS) {
            $payload['ai_verification'] = [
                'status' => 'pending',
                'recommendation' => null,
                'message' => 'Identity verification has started and runs in the background.',
                'status_url' => url('/api/v1/verification/ai/status'),
                'retry_url' => url('/api/v1/verification/ai/run'),
            ];
        }

        return $this->success($payload, 'Registration step '.$step.' saved.');
    }

    public function status(Request $request): JsonResponse
    {
        return $this->success($this->registration->status($request->user()));
    }
}
