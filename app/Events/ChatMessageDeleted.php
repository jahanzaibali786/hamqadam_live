<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly int $messageId,
        public readonly int $deletedByUserId,
        public readonly string $deletedAt
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'message-deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'message_id' => $this->messageId,
            'deleted_by_user_id' => $this->deletedByUserId,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
