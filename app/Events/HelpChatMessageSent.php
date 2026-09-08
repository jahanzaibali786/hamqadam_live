<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\HelpChatMessage;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

/**
 * One Help Center message, pushed to both sides in real time.
 *
 * Channels:
 *
 *   private-help-chat.{threadId}   the conversation itself. The member's app
 *                                  subscribes while the Help chat is open;
 *                                  the admin panel subscribes for the whole
 *                                  session (channel authorization below only
 *                                  admits staff and the thread's owner).
 *   private-App.User.{recipient}   the side that is not looking at the
 *                                  conversation right now — same channel
 *                                  inbox previews and call signals ride, so
 *                                  one subscription covers everything.
 *
 * `broadcastAs` values are consumed verbatim by the app's Pusher service and
 * by the admin panel's Echo shim, so renaming them breaks both.
 */
class HelpChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly HelpChatMessage $message,
        public readonly int $threadId,
        public readonly int $memberUserId,
        public readonly bool $fromAdmin
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('help-chat.' . $this->threadId),
        ];

        // The side that did NOT send it is the side that has to be woken.
        // When a member writes, the configured support inbox (or the first
        // admin) gets the wake-up on their own user channel.
        $recipientId = $this->fromAdmin
            ? $this->memberUserId
            : ((int) config('helpchat.admin_notify_user_id', 0) ?: $this->firstAdminId());

        if ($recipientId > 0) {
            $channels[] = new PrivateChannel('App.User.' . $recipientId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'help-message-sent';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'id' => $this->message->id,
            'thread_id' => $this->threadId,
            'from_admin' => $this->fromAdmin,
            'sender' => [
                'id' => $sender?->id,
                'name' => $this->senderName(),
                'photo' => $sender && $sender->photo ? uploaded_asset($sender->photo) : null,
            ],
            'message' => (string) ($this->message->message ?? ''),
            'message_type' => (string) ($this->message->message_type ?? 'text'),
            'attachments' => $this->attachments(),
            'seen' => (bool) $this->message->seen,
            'created_at' => optional($this->message->created_at)->toISOString(),
        ];
    }

    private function senderName(): string
    {
        $sender = $this->message->sender;
        if (! $sender) {
            return $this->fromAdmin ? 'HamQadam Support' : 'Member';
        }

        if ($this->fromAdmin) {
            $name = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));

            return $name !== '' ? $name : 'HamQadam Support';
        }

        return trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));
    }

    /**
     * Same attachment shape ChatMessageSent publishes, so one parser serves
     * both chats in the app.
     */
    private function attachments(): array
    {
        $ids = $this->message->attachmentIds();
        if ($ids === []) {
            return [];
        }

        $attachments = [];

        foreach ($ids as $id) {
            $upload = Upload::find($id);

            if (! $upload) {
                $attachments[] = [
                    'id' => $id,
                    'type' => 'file',
                    'url' => uploaded_asset($id),
                    'download_url' => route('download_attachment', $id),
                    'name' => 'Attachment',
                    'original_name' => 'Attachment',
                    'extension' => '',
                    'size' => null,
                    'preview_url' => uploaded_asset($id),
                ];

                continue;
            }

            $attachments[] = [
                'id' => (int) $upload->id,
                'type' => (string) $upload->type,
                'url' => uploaded_asset((int) $upload->id),
                'download_url' => route('download_attachment', (int) $upload->id),
                'name' => (string) $upload->file_name,
                'original_name' => (string) $upload->file_original_name,
                'extension' => (string) $upload->extension,
                'size' => $upload->file_size !== null ? (int) $upload->file_size : null,
                'preview_url' => static_asset($upload->file_name),
            ];
        }

        return $attachments;
    }

    /**
     * Fallback for `App.User` targeting when no admin id is configured.
     */
    private function firstAdminId(): int
    {
        return (int) (User::query()
            ->where('user_type', 'admin')
            ->orderBy('id')
            ->value('id') ?? 0);
    }
}
