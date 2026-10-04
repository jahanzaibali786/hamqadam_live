<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'expected_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'rated_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ((string) $this->status) {
            '0' => 'Open',
            '1' => 'Resolved',
            '2' => 'In Progress',
            '3' => 'Waiting for Member',
            default => 'Open',
        };
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class);
    }
}
