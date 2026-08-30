<?php

declare(strict_types=1);

namespace App\Enums;

enum CallType: string
{
    case Audio = 'audio';
    case Video = 'video';
}
