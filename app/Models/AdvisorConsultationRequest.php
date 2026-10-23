<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvisorConsultationRequest extends Model
{
    public const STATUSES = ['new', 'contacted', 'scheduled', 'completed', 'cancelled'];

    protected $fillable = [
        'name', 'email', 'phone', 'city', 'advisor', 'status',
        'admin_notes', 'notification_status', 'notification_sent_at',
    ];

    protected $casts = [
        'notification_sent_at' => 'datetime',
    ];
}
