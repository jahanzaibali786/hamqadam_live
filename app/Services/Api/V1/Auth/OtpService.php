<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Contracts\Repositories\AuthOtpCodeRepository;
use App\Enums\Auth\OtpChannel;
use App\Enums\Auth\OtpPurpose;
use App\Exceptions\ApiException;
use App\Models\AuthOtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly AuthOtpCodeRepository $otpCodes)
    {
    }

    public function issue(
        string $identifier,
        OtpPurpose $purpose,
        OtpChannel $channel,
        ?User $user = null
    ): array {
        $code = (string) random_int(100000, 999999);

        $otp = $this->otpCodes->create([
            'user_id' => $user?->id,
            'channel' => $channel->value,
            'identifier' => $this->normalizeIdentifier($identifier, $channel),
            'purpose' => $purpose->value,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return [
            'otp' => $otp,
            'code' => $code,
        ];
    }

    public function verify(
        string $identifier,
        string $code,
        OtpPurpose $purpose,
        OtpChannel $channel
    ): AuthOtpCode {
        $otp = $this->otpCodes->latestActive(
            $this->normalizeIdentifier($identifier, $channel),
            $purpose->value,
            $channel->value
        );

        if (! $otp) {
            throw new ApiException('Invalid or expired verification code.', 422, 'invalid_otp');
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw new ApiException('Too many verification attempts.', 429, 'otp_attempts_exceeded');
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw new ApiException('Invalid or expired verification code.', 422, 'invalid_otp');
        }

        $otp->verified_at = now();
        $otp->save();

        return $otp;
    }

    public function normalizeIdentifier(string $identifier, OtpChannel $channel): string
    {
        $identifier = trim($identifier);

        return $channel === OtpChannel::Email
            ? Str::lower($identifier)
            : preg_replace('/\s+/', '', $identifier);
    }
}

