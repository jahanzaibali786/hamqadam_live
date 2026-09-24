<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One swipe in the Swipe Matching deck.
 *
 * `like` means interested, `pass` means skipped. A row is per direction, so a
 * mutual match is "a like from me AND a like from them" — no extra table.
 */
class ProfileSwipe extends Model
{
    public const LIKE = 'like';

    public const PASS = 'pass';

    protected $fillable = [
        'swiper_user_id',
        'target_user_id',
        'action',
    ];

    public function swiper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swiper_user_id')->withTrashed();
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id')->withTrashed();
    }

    public function scopeLikes($query)
    {
        return $query->where('action', self::LIKE);
    }
}
