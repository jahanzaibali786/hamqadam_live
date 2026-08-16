<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePrivacySetting extends Model
{
    protected $fillable = [
        'user_id',
        'show_photo',
        'show_gallery',
        'show_contact',
        'show_email',
        'show_phone',
        'show_location',
        'allow_profile_view_notifications',
        'do_not_disturb',
        'invisible_mode',
    ];

    protected $casts = [
        'show_photo' => 'boolean',
        'show_gallery' => 'boolean',
        'show_contact' => 'boolean',
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'show_location' => 'boolean',
        'allow_profile_view_notifications' => 'boolean',
        'do_not_disturb' => 'boolean',
        'invisible_mode' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
