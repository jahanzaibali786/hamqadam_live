<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Dto\Auth\DeviceData;
use App\Enums\Auth\OtpChannel;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\EmailLoginRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\GoogleLoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\RequestEmailVerificationRequest;
use App\Http\Requests\Api\V1\Auth\RequestMobileOtpRequest;
use App\Http\Requests\Api\V1\Auth\RequestRegistrationOtpRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Http\Requests\Api\V1\Auth\VerifyMobileOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyRegistrationOtpRequest;
use App\Http\Resources\Api\V1\Auth\AuthTokenResource;
use App\Http\Resources\Api\V1\Auth\DeviceSessionResource;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Models\UserDeviceSession;
use App\Services\Api\V1\Auth\AuthService;
use App\Services\Api\V1\Auth\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AuthTokenService $tokenService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        
        $token = $this->authService->register(
            data: $request->validated(),
            deviceData: DeviceData::fromRequest($request)
        );

        return $this->success(new AuthTokenResource($token), 'Registered successfully.', 201);
    }

    public function emailLogin(EmailLoginRequest $request): JsonResponse
    {
        $token = $this->authService->loginWithEmail(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceData: DeviceData::fromRequest($request)
        );

        return $this->success(new AuthTokenResource($token), 'Logged in successfully.');
    }

    public function googleLogin(GoogleLoginRequest $request): JsonResponse
    {
        $token = $this->authService->loginWithGoogle(
            accessToken: $request->string('access_token')->toString(),
            deviceData: DeviceData::fromRequest($request)
        );

        return $this->success(new AuthTokenResource($token), 'Logged in successfully.');
    }

    public function requestMobileOtp(RequestMobileOtpRequest $request): JsonResponse
    {
        $otp = $this->authService->requestMobileLoginOtp($request->string('phone')->toString());

        $meta = [];
        if (! app()->environment('production')) {
            $meta['debug_otp'] = $otp['code'];
        }

        return $this->success(
            data: [
                'expires_at' => $otp['otp']->expires_at->toISOString(),
            ],
            message: 'Verification code sent.',
            meta: $meta
        );
    }

    public function verifyMobileOtp(VerifyMobileOtpRequest $request): JsonResponse
    {
        $token = $this->authService->verifyMobileLoginOtp(
            phone: $request->string('phone')->toString(),
            code: $request->string('code')->toString(),
            deviceData: DeviceData::fromRequest($request)
        );

        return $this->success(new AuthTokenResource($token), 'Logged in successfully.');
    }

    public function requestEmailVerification(RequestEmailVerificationRequest $request): JsonResponse
    {
        $otp = $this->authService->requestEmailVerification(
            user: $request->user(),
            email: $request->string('email')->toString() ?: null
        );

        $meta = [];
        if (! app()->environment('production')) {
            $meta['debug_otp'] = $otp['code'];
        }

        return $this->success(
            data: [
                'expires_at' => $otp['otp']->expires_at->toISOString(),
            ],
            message: 'Verification code sent.',
            meta: $meta
        );
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->authService->verifyEmail(
            user: $request->user(),
            code: $request->string('code')->toString(),
            email: $request->string('email')->toString() ?: null
        );

        return $this->success(new UserResource($user), 'Email verified successfully.');
    }

    public function requestRegistrationOtp(RequestRegistrationOtpRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();

        $otp = $this->authService->requestRegistrationOtp(
            user: $request->user(),
            email: $email ?: null
        );

        $meta = [];
        if (! app()->environment('production')) {
            $meta['debug_otp'] = $otp['code'];
        }

        return $this->success(
            data: [
                'expires_at' => $otp['otp']->expires_at->toISOString(),
            ],
            message: 'Verification code sent.',
            meta: $meta
        );
    }

    public function verifyRegistrationOtp(VerifyRegistrationOtpRequest $request): JsonResponse
    {
        $this->authService->verifyRegistrationOtp(
            user: $request->user(),
            code: $request->string('code')->toString(),
            email: $request->string('email')->toString() ?: null
        );

        return $this->success(message: 'Registration verified successfully.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $otp = $this->authService->requestPasswordReset(
            identifier: $request->string('identifier')->toString(),
            channel: OtpChannel::from($request->string('channel')->toString())
        );

        $meta = [];
        if (! app()->environment('production')) {
            $meta['debug_otp'] = $otp['code'];
        }

        return $this->success(
            data: [
                'expires_at' => $otp['otp']->expires_at->toISOString(),
            ],
            message: 'Password reset code sent.',
            meta: $meta
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            identifier: $request->string('identifier')->toString(),
            channel: OtpChannel::from($request->string('channel')->toString()),
            code: $request->string('code')->toString(),
            password: $request->string('password')->toString()
        );

        return $this->success(message: 'Password reset successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $this->tokenService->touchCurrentSession($request);

        return $this->success(new UserResource($request->user()->loadMissing('member.package')));
    }

    public function devices(Request $request): JsonResponse
    {
        $sessions = UserDeviceSession::where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return DeviceSessionResource::collection($sessions)
            ->additional(['success' => true])
            ->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->revokeCurrent($request);

        return $this->success(message: 'Logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->tokenService->revokeAll($request->user());

        return $this->success(message: 'All device sessions have been logged out.');
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['deactivated' => 1])->save();
        $this->tokenService->revokeAll($user);

        return $this->success(message: 'Account deactivated successfully.');
    }
}
