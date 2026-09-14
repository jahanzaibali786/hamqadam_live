<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'identifier_hash',
        'identifier_hint',
        'channel',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}