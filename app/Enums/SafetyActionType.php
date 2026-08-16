<?php

declare(strict_types=1);

namespace App\Enums;

enum SafetyActionType: string
{
    case Report = 'report';
    case Block = 'block';
    case Mute = 'mute';
    case Restrict = 'restrict';
}
