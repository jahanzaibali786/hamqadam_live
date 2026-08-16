<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum DeviceType: string
{
    case Android = 'android';
    case Ios = 'ios';
    case Web = 'web';
    case Unknown = 'unknown';
}

