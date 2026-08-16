<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPost extends Model
{
    protected $fillable = ['community_thread_id', 'user_id', 'body', 'moderation_status'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunityThread::class, 'community_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
