<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One message inside a Help Center conversation.
 *
 * `sender_user_id` is whoever typed it — the member OR the admin replying from
 * the panel; there is no separate admin column, the thread's owner tells the
 * two apart. Attachments reuse the `uploads` table the same way `chats`
 * does: comma-separated upload ids in `attachment`.
 */
class HelpChatMessage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function thread()
    {
        return $this->belongsTo(HelpChatThread::class, 'thread_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Upload ids attached to this message, as ints.
     *
     * @return list<int>
     */
    public function attachmentIds(): array
    {
        if (! $this->attachment) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', explode(',', (string) $this->attachment)),
            static fn (int $id) => $id > 0,
        ));
    }
}
