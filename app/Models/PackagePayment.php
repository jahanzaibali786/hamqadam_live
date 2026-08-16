<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackagePayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'discount_amount' => 'float',
        'payable_amount' => 'float',
        'paid_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function package()
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function coupon()
    {
        return $this->belongsTo(PaymentCoupon::class, 'coupon_id');
    }
}
