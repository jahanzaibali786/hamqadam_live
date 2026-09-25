<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianFeedback extends Model
{
    protected $fillable = [
        'guardian_link_id',
        'guardian_user_id',
        'profile_user_id',
        'target_user_id',
        'feedback_type',
        'reason',
        'comment',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(FamilyGuardianLink::class, 'guardian_link_id');
    }
}
