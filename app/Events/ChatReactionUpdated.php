<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a member adds, changes or clears an emoji reaction on a message
 * (POST /chat/messages/{message}/reaction). Both clients listen on the thread
 * channel and swap the pill under the bubble without refetching history.
 *
 * `emoji` is null when the reaction was removed / toggled off.
 */
class ChatReactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly int $messageId,
        public readonly int $userId,
        public readonly ?string $emoji
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.'.$this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'message-reaction';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'emoji' => $this->emoji,
        ];
    }
}
