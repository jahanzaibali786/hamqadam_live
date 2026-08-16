<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum OtpPurpose: string
{
    case Login = 'login';
    case PasswordReset = 'password_reset';
    case Verification = 'verification';
    case Registration = 'registration';
}

