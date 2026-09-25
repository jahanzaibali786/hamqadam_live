<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianInvitation extends Model
{
    protected $fillable = [
        'profile_user_id',
        'guardian_user_id',
        'contact',
        'relationship',
        'guardian_role',
        'is_wali',
        'permissions',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'attempts',
    ];

    protected $casts = [
        'is_wali' => 'boolean',
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === 'pending' && ! $this->expires_at->isPast();
    }
}
