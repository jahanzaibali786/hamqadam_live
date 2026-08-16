<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyGuardianLink extends Model
{
    protected $fillable = [
        'profile_user_id',
        'guardian_user_id',
        'relationship',
        'guardian_role',
        'is_wali',
        'status',
        'permissions',
        'digest_frequency',
        'approved_at',
        'last_digest_sent_at',
    ];

    protected $casts = [
        'is_wali' => 'boolean',
        'permissions' => 'array',
        'approved_at' => 'datetime',
        'last_digest_sent_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }
}
