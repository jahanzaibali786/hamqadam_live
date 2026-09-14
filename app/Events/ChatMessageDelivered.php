<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the recipient's app acknowledges receiving a thread's messages
 * (POST /chat/threads/{thread}/delivered). The sender listens for this to turn
 * the single tick into a double tick — delivered means "on their device", while
 * ChatMessageRead (already broadcast) means "they opened it".
 */
class ChatMessageDelivered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly array $messageIds,
        public readonly int $deliveredToUserId,
        public readonly string $deliveredAt
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.'.$this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'message-delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'message_ids' => $this->messageIds,
            'delivered_to_user_id' => $this->deliveredToUserId,
            'delivered_at' => $this->deliveredAt,
        ];
    }
}
