<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfileVerificationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'cnic_number',
        'face_match_status',
        'face_match_score',
        'rejection_reason',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'status' => VerificationRequestStatus::class,
        'face_match_score' => 'float',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProfileVerificationDocument::class);
    }
}
