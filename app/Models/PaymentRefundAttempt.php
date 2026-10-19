<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentRefundAttempt extends Model
{
    protected $fillable = [
        'refund_request_id', 'package_payment_id', 'provider', 'idempotency_key',
        'amount_minor', 'currency', 'status', 'provider_refund_id', 'last_error', 'provider_checked_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'provider_checked_at' => 'datetime',
    ];

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRefundRequest::class, 'refund_request_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PackagePayment::class, 'package_payment_id');
    }
}
