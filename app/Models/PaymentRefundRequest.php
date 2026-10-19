<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRefundRequest extends Model
{
    protected $fillable = [
        'package_payment_id', 'user_id', 'status', 'amount', 'currency',
        'reason', 'provider_refund_reference', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];
    protected $casts = ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];
    public function payment() { return $this->belongsTo(PackagePayment::class, 'package_payment_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
