<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCoupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'minimum_amount' => 'float',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function isNotStartedYet(): bool
    {
        if (! $this->starts_at) {
            return false;
        }

        return $this->starts_at->copy()->startOfDay()->isFuture();
    }

    public function hasExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->copy()->endOfDay()->isPast();
    }
}
