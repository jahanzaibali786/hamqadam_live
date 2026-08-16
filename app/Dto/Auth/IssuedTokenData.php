<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Models\User;
use App\Models\UserDeviceSession;
use App\Support\Dto\ArrayData;
use Carbon\CarbonInterface;

readonly class IssuedTokenData extends ArrayData
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
        public CarbonInterface $expiresAt,
        public UserDeviceSession $deviceSession,
    ) {
    }
}

