<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum OtpChannel: string
{
    case Sms = 'sms';
    case Email = 'email';
}

