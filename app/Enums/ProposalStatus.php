<?php

declare(strict_types=1);

namespace App\Enums;

enum ProposalStatus: int
{
    case Pending = 0;
    case Accepted = 1;
    case Rejected = 2;
    case Withdrawn = 3;
    case Cancelled = 4;
    case Expired = 5;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Accepted => 'accepted',
            self::Rejected => 'rejected',
            self::Withdrawn => 'withdrawn',
            self::Cancelled => 'cancelled',
            self::Expired => 'expired',
        };
    }
}
