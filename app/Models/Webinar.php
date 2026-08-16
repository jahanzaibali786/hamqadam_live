<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webinar extends Model
{
    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'duration_minutes',
        'host_name',
        'meeting_url',
        'status',
    ];

    protected $casts = ['starts_at' => 'datetime'];
}
