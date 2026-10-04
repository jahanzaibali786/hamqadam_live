<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'event_preferences',
        'quiet_hours_start',
        'quiet_hours_end',
        'timezone',
        'digest_frequency',
        'reminder_interval_minutes',
        'reminder_max_attempts',
        'engagement_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'event_preferences' => 'array',
        'engagement_enabled' => 'boolean',
        'reminder_interval_minutes' => 'integer',
        'reminder_max_attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
