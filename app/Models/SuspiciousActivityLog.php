<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuspiciousActivityLog extends Model
{
    protected $fillable = ['user_id', 'activity_type', 'risk_level', 'risk_score', 'signals', 'reviewed_at'];

    protected $casts = [
        'risk_score' => 'float',
        'signals' => 'array',
        'reviewed_at' => 'datetime',
    ];
}
