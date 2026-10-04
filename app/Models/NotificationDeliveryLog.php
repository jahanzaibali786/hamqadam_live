<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryLog extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'channel',
        'status',
        'error_message',
        'payload',
        'sent_at',
        'failure_reason',
        'last_reminded_at',
        'reminder_count',
        'deep_link',
        'clicked_at',
        'read_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'reminder_count' => 'integer',
        'last_reminded_at' => 'datetime',
        'clicked_at' => 'datetime',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
