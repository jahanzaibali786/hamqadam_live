<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

abstract class CallBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat-thread.' . ($this->payload['call']['conversation_id'] ?? 0)),
        ];

        foreach (($this->recipientIds() ?? []) as $recipientId) {
            $channels[] = new PrivateChannel('App.User.' . (int) $recipientId);
        }

        return $channels;
    }

    protected function recipientIds(): array
    {
        return [];
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
