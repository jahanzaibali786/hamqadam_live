<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ChatMessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly array $messageIds,
        public readonly int $readByUserId,
        public readonly string $readAt
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'message-read';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'message_ids' => $this->messageIds,
            'read_by_user_id' => $this->readByUserId,
            'read_at' => $this->readAt,
        ];
    }
}
