<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentGateway: string
{
    case Stripe = 'stripe';
    case EasyPaisa = 'easypaisa';
    case JazzCash = 'jazzcash';

    public function id(): int
    {
        return match ($this) {
            self::Stripe => 1,
            self::EasyPaisa => 2,
            self::JazzCash => 3,
        };
    }

    public static function fromId(int $id): self
    {
        return match ($id) {
            1 => self::Stripe,
            2 => self::EasyPaisa,
            3 => self::JazzCash,
            default => throw new \ValueError('Invalid payment gateway id: ' . $id),
        };
    }
}
