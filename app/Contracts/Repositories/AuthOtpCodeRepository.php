<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\AuthOtpCode;

interface AuthOtpCodeRepository extends Repository
{
    public function create(array $data): AuthOtpCode;

    public function latestActive(string $identifier, string $purpose, string $channel): ?AuthOtpCode;
}

