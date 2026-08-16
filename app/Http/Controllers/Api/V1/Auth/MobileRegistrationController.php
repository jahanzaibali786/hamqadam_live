<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Dto\Auth\DeviceData;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Auth\UserResource;
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
            DeviceData::fromRequest($request)
        );

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expiresAt->toISOString(),
            'user' => new UserResource($token->user->load('member')),
            'registration' => $this->registration->status($token->user),
        ], 'Registration submitted successfully. Please verify your email to complete registration.', 201);
    }
}
