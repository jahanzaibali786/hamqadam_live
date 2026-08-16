<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityThread extends Model
{
    protected $fillable = [
        'community_forum_id',
        'user_id',
        'title',
        'body',
        'moderation_status',
        'is_locked',
    ];

    protected $casts = ['is_locked' => 'boolean'];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(CommunityForum::class, 'community_forum_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }
}
