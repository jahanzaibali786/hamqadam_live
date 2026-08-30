<?php

declare(strict_types=1);

namespace App\Enums;

enum CallStatus: string
{
    case Calling = 'calling';
    case Ringing = 'ringing';
    case Accepted = 'accepted';
    case Connected = 'connected';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Busy = 'busy';
    case Missed = 'missed';
    case Ended = 'ended';
    case Failed = 'failed';
}
