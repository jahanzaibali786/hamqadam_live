<?php

declare(strict_types=1);

namespace App\Enums;

enum ModerationCaseStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
