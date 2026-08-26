<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ChatTypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly User $user,
        public readonly bool $isTyping,
        public readonly string $expiresAt
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'typing-indicator';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'user' => [
                'id' => $this->user->id,
                'name' => trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? '')),
                'photo' => $this->user->photo ? uploaded_asset($this->user->photo) : null,
            ],
            'is_typing' => $this->isTyping,
            'expires_at' => $this->expiresAt,
        ];
    }
}
