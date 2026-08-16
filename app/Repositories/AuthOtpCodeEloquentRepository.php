<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AuthOtpCodeRepository;
use App\Models\AuthOtpCode;

class AuthOtpCodeEloquentRepository implements AuthOtpCodeRepository
{
    public function create(array $data): AuthOtpCode
    {
        return AuthOtpCode::create($data);
    }

    public function latestActive(string $identifier, string $purpose, string $channel): ?AuthOtpCode
    {
        return AuthOtpCode::query()
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}

