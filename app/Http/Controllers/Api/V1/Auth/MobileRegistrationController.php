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

class MobileRegistrationController extends ApiController
{
    public function __construct(private readonly StepwiseRegistrationService $registration)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $token = $this->registration->completeRegistration(
            $request->all(),
            DeviceData::fromRequest($request),
            $request
        );

        $user = $token->user;

        /*
         * AI identity verification is fired here, AFTER registration has
         * already succeeded, and deliberately out of band:
         *
         *  - Registration must never fail or slow down because the model is
         *    busy or offline. dispatchAfterResponse() runs the job once this
         *    response has been sent, which keeps that true even on
         *    QUEUE_CONNECTION=sync.
         *  - The request payload is untouched. The app keeps posting exactly
         *    what it posted before.
         *  - Because it runs after the response, the final recommendation is
         *    not available yet. We return `ai_verification` with the current
         *    state so the app can poll GET /api/v1/verification/ai/status,
         *    or retry via POST /api/v1/verification/ai/run.
         */
        RunAiVerification::dispatchAfterResponse(
            $user->id,
            AiVerificationAttempt::SOURCE_REGISTRATION_API
        );

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expiresAt->toISOString(),
            'user' => new UserResource($user->load('member')),
            'registration' => $this->registration->status($user),
            'ai_verification' => [
                'status' => 'pending',
                'recommendation' => null,
                'message' => 'Identity verification has started and runs in the background.',
                'status_url' => url('/api/v1/verification/ai/status'),
                'retry_url' => url('/api/v1/verification/ai/run'),
            ],
        ], 'Registration submitted successfully. Please verify your email to complete registration.', 201);
    }
}
