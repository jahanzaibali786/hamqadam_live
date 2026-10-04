<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'seen_by_receiver' => 'boolean',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDeliveryLog::class, 'notification_id', 'id');
    }
}
