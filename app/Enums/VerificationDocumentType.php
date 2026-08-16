<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationDocumentType: string
{
    case CnicFront = 'cnic_front';
    case CnicBack = 'cnic_back';
    case Selfie = 'selfie';
    case Face = 'face';
    case Other = 'other';
}
