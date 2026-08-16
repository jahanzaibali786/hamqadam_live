<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatThread extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'sender_muted_at' => 'datetime',
        'receiver_muted_at' => 'datetime',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function blocked_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user');
    }

    public function typingIndicators(): HasMany
    {
        return $this->hasMany(ChatTypingIndicator::class, 'chat_thread_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}
