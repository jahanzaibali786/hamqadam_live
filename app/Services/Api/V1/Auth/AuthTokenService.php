<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Dto\Auth\DeviceData;
use App\Dto\Auth\IssuedTokenData;
use App\Models\User;
use App\Models\UserDeviceSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AuthTokenService
{
    private const TOKEN_NAME = 'hamqadam-v1';
    private const TOKEN_TTL_DAYS = 30;

    public function issue(User $user, DeviceData $deviceData): IssuedTokenData
    {
        $expiresAt = CarbonImmutable::now()->addDays(self::TOKEN_TTL_DAYS);
        $token = $user->createToken(self::TOKEN_NAME, ['*'], $expiresAt);

        $session = UserDeviceSession::create([
            'user_id' => $user->id,
            'personal_access_token_id' => $token->accessToken->id,
            'device_name' => $deviceData->deviceName,
            'device_type' => $deviceData->deviceType->value,
            'device_id' => $deviceData->deviceId,
            'ip_address' => $deviceData->ipAddress,
            'user_agent' => $deviceData->userAgent,
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return new IssuedTokenData($user, $token->plainTextToken, $expiresAt, $session);
    }

    public function touchCurrentSession(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return;
        }

        UserDeviceSession::where('personal_access_token_id', $token->id)
            ->whereNull('revoked_at')
            ->update([
                'last_used_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
    }

    public function revokeCurrent(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return;
        }

        UserDeviceSession::where('personal_access_token_id', $token->id)->update([
            'revoked_at' => now(),
        ]);

        $token->delete();
    }

    public function revokeAll(User $user): void
    {
        UserDeviceSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $user->tokens()->delete();
    }
}

