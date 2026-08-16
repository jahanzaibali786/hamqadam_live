<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationCaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationCase extends Model
{
    protected $fillable = [
        'reported_user_id',
        'reporter_user_id',
        'assigned_to',
        'case_type',
        'status',
        'severity',
        'reason',
        'evidence',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'status' => ModerationCaseStatus::class,
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }
}
