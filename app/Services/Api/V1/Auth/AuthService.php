<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Contracts\Repositories\UserRepository;
use App\Dto\Auth\DeviceData;
use App\Dto\Auth\IssuedTokenData;
use App\Enums\Auth\OtpChannel;
use App\Enums\Auth\OtpPurpose;
use App\Exceptions\ApiException;
use App\Models\Member;
use App\Models\User;
use App\Services\Api\V1\Concerns\ExecutesInTransaction;
use App\Services\Api\V1\Profile\ProfileCompletionService;
use App\Support\RegistrationOnboarding;
use App\Support\RegistrationReward;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    use ExecutesInTransaction;

    public function __construct(
        private readonly UserRepository $users,
        private readonly OtpService $otpService,
        private readonly AuthTokenService $tokenService,
        private readonly ProfileCompletionService $profileCompletion,
    ) {
    }

    public function loginWithEmail(string $email, string $password, DeviceData $deviceData): IssuedTokenData
    {
        $user = $this->users->findForEmailLogin($email);
        // dd($user);
        if (!$user || !Hash::check($password, (string) $user->password)) {
            throw new ApiException('Invalid email or password.', 401, 'invalid_credentials');
        }

        $this->assertCanLogin($user);

        return $this->transaction(fn() => $this->tokenService->issue($user, $deviceData));
    }


    public function register(array $data, DeviceData $deviceData): IssuedTokenData
    {
        return DB::transaction(function () use ($data, $deviceData) {

            $user = User::create([
                'user_type' => 'member',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'name' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'code' => unique_code(),
                'membership' => 1,
                'approved' => 1,
                'email_verified_at' => now(),
            ]);

            // Referral
            if (
                addon_activation('referral_system') &&
                !empty($data['referral_code'])
            ) {
                $referrer = User::where('code', $data['referral_code'])->first();

                if ($referrer) {
                    $user->update([
                        'referred_by' => $referrer->id,
                    ]);
                }
            }

            // Member Profile
            Member::create([
                'user_id' => $user->id,
                'gender' => $data['gender'],
                'birthday' => Carbon::parse($data['date_of_birth'])->toDateString(),
                'on_behalves_id' => $data['on_behalf'] ?? null,
            ]);

            $user->load('member');

            // Save onboarding (only step 1 data or whatever is available)
            RegistrationOnboarding::persist($user, $data);

            // Assign default package/rewards
            RegistrationReward::applyRegistrationDefaultPackage($user);

            // Reload latest relations if needed
            $user->refresh()->load('member');

            // Issue Sanctum token
            return $this->tokenService->issue($user, $deviceData);
        });
    }

    public function requestMobileLoginOtp(string $phone): array
    {
        $user = $this->users->findForPhoneLogin($phone);

        if (!$user) {
            throw new ApiException('No member account exists for this phone number.', 404, 'user_not_found');
        }

        $this->assertCanLogin($user);

        return $this->otpService->issue($phone, OtpPurpose::Login, OtpChannel::Sms, $user);
    }

    public function verifyMobileLoginOtp(string $phone, string $code, DeviceData $deviceData): IssuedTokenData
    {
        $otp = $this->otpService->verify($phone, $code, OtpPurpose::Login, OtpChannel::Sms);
        $user = $otp->user ?: $this->users->findForPhoneLogin($phone);

        if (!$user) {
            throw new ApiException('No member account exists for this phone number.', 404, 'user_not_found');
        }

        $this->assertCanLogin($user);

        return $this->transaction(fn() => $this->tokenService->issue($user, $deviceData));
    }

    public function requestEmailVerification(User $user, ?string $email = null): array
    {
        $targetEmail = $email ?: $user->email;

        if (!$targetEmail) {
            throw new ApiException('This account does not have an email address.', 422, 'email_missing');
        }

        if ($email && $email !== $user->email) {
            if (User::where('email', $email)->whereKeyNot($user->id)->exists()) {
                throw new ApiException('This email address is already in use.', 409, 'email_already_exists');
            }

            $user->email = $email;
            $user->email_verified_at = null;
            $user->save();
        }

        if ($user->email_verified_at) {
            throw new ApiException('Email is already verified.', 409, 'email_already_verified');
        }

        return $this->otpService->issue($targetEmail, OtpPurpose::Verification, OtpChannel::Email, $user);
    }

    public function verifyEmail(User $user, string $code, ?string $email = null): User
    {
        $targetEmail = $email ?: $user->email;

        if (!$targetEmail) {
            throw new ApiException('This account does not have an email address.', 422, 'email_missing');
        }

        $this->otpService->verify($targetEmail, $code, OtpPurpose::Verification, OtpChannel::Email);

        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function requestPasswordReset(string $identifier, OtpChannel $channel): array
    {
        $user = $channel === OtpChannel::Email
            ? $this->users->findForEmailLogin($identifier)
            : $this->users->findForPhoneLogin($identifier);

        if (!$user) {
            throw new ApiException('No member account exists for this identifier.', 404, 'user_not_found');
        }

        return $this->otpService->issue($identifier, OtpPurpose::PasswordReset, $channel, $user);
    }

    public function resetPassword(string $identifier, OtpChannel $channel, string $code, string $password): void
    {
        $otp = $this->otpService->verify($identifier, $code, OtpPurpose::PasswordReset, $channel);
        $user = $otp->user ?: (
            $channel === OtpChannel::Email
            ? $this->users->findForEmailLogin($identifier)
            : $this->users->findForPhoneLogin($identifier)
        );

        if (!$user) {
            throw new ApiException('No member account exists for this identifier.', 404, 'user_not_found');
        }

        $user->password = Hash::make($password);
        $user->verification_code = null;
        $user->save();

        $this->tokenService->revokeAll($user);
    }

    public function requestRegistrationOtp(User $user, ?string $email = null): array
    {
        $targetEmail = $email ?: $user->email;

        if (!$targetEmail) {
            throw new ApiException('This account does not have an email address.', 422, 'email_missing');
        }

        if ($email && $email !== $user->email) {
            if (User::where('email', $email)->whereKeyNot($user->id)->exists()) {
                throw new ApiException('This email address is already in use.', 409, 'email_already_exists');
            }
        }

        if ($user->email_verified_at) {
            throw new ApiException('Email is already verified.', 409, 'email_already_verified');
        }

        return $this->otpService->issue($targetEmail, OtpPurpose::Registration, OtpChannel::Email, $user);
    }

    public function verifyRegistrationOtp(User $user, string $code, ?string $email = null): void
    {
        $targetEmail = $email ?: $user->email;

        if (!$targetEmail) {
            throw new ApiException('This account does not have an email address.', 422, 'email_missing');
        }

        $this->otpService->verify($targetEmail, $code, OtpPurpose::Registration, OtpChannel::Email);

        if ($email && $email !== $user->email) {
            $user->email = $email;
        }

        $user->email_verified_at = now();
        $user->forceFill([
            'membership' => 1,
            'approved' => 1,
        ])->save();

        RegistrationReward::applyRegistrationDefaultPackage($user);

        $completion = $this->profileCompletion->calculate($user->fresh());
        $user->member?->forceFill([
            'profile_completion_percentage' => $completion,
            'registration_completed_at' => now(),
        ])->save();
    }

    public function loginWithGoogle(string $accessToken, DeviceData $deviceData): IssuedTokenData
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);
        } catch (\Throwable $exception) {
            throw new ApiException('Google authentication failed.', 401, 'google_auth_failed', previous: $exception);
        }

        $email = $googleUser->getEmail();
        if (!$email) {
            throw new ApiException('Google account did not return an email address.', 422, 'google_email_missing');
        }

        return $this->transaction(function () use ($googleUser, $email, $deviceData) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                throw new ApiException('Please complete full registration before using Google login.', 422, 'registration_required');
            }

            $user->provider_id = $googleUser->getId();
            $user->save();

            $this->assertCanLogin($user);

            return $this->tokenService->issue($user, $deviceData);
        });
    }

    private function assertCanLogin(User $user): void
    {
        if ((int) $user->blocked === 1) {
            throw new ApiException('This account is blocked.', 403, 'account_blocked');
        }

        if ((int) $user->deactivated === 1) {
            throw new ApiException('This account is deactivated.', 403, 'account_deactivated');
        }

        if ((int) $user->approved === 0) {
            throw new ApiException('This account is pending approval.', 403, 'account_pending_approval');
        }
    }
}
