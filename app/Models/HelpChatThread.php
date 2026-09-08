<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One Help Center conversation per member.
 *
 * Created lazily by HelpChatService the first time a member sends a message,
 * so the admin list only ever contains members who actually asked for help.
 * The app reaches it through `GET /help-chat/thread`, which creates it on
 * demand — the member never has to press "new conversation".
 */
class HelpChatThread extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(HelpChatMessage::class, 'thread_id');
    }

    public function lastMessage()
    {
        return $this->belongsTo(HelpChatMessage::class, 'last_message_id');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * The member who owns the conversation.
     */
    public function owner(): User
    {
        return $this->user()->firstOrFail();
    }
}
