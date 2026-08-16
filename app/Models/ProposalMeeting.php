<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalMeeting extends Model
{
    protected $fillable = [
        'express_interest_id',
        'organizer_user_id',
        'chaperone_user_id',
        'meeting_type',
        'status',
        'scheduled_at',
        'duration_minutes',
        'meeting_url',
        'location',
        'chaperone_mode',
        'recording_consent_requested',
        'recording_consent_status',
        'recording_url',
        'pre_meeting_questionnaire',
        'post_meeting_feedback',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'chaperone_mode' => 'boolean',
        'recording_consent_requested' => 'boolean',
        'pre_meeting_questionnaire' => 'array',
        'post_meeting_feedback' => 'array',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'express_interest_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function chaperone(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chaperone_user_id');
    }
}
