<?php

namespace App\Models;

use App\Enums\ChatMessageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $guarded =[];

    protected $casts = [
        'message_type' => ChatMessageType::class,
        'seen' => 'boolean',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'deleted_by_sender_at' => 'datetime',
        'deleted_by_receiver_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function chatThread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_chat_id');
    }
}
