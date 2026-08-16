<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Chat $message,
        public readonly User $sender,
        public readonly int $threadId
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-thread.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'message-sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'thread_id' => $this->threadId,
            'sender' => [
                'id' => $this->sender->id,
                'code' => $this->sender->code,
                'name' => trim(($this->sender->first_name ?? '') . ' ' . ($this->sender->last_name ?? '')),
                'photo' => $this->sender->photo ? uploaded_asset($this->sender->photo) : null,
            ],
            'message' => $this->message->message,
            'message_type' => $this->message->message_type instanceof \App\Enums\ChatMessageType
                ? $this->message->message_type->value
                : ($this->message->message_type ?? 'text'),
            'attachments' => $this->attachments(),
            'reply_to' => $this->message->relationLoaded('replyTo') && $this->message->replyTo ? [
                'id' => $this->message->replyTo->id,
                'message' => $this->message->replyTo->message,
                'sender' => $this->message->replyTo->sender ? [
                    'id' => $this->message->replyTo->sender->id,
                    'name' => trim(($this->message->replyTo->sender->first_name ?? '') . ' ' . ($this->message->replyTo->sender->last_name ?? '')),
                ] : null,
            ] : null,
            'delivered_at' => optional($this->message->delivered_at)->toISOString(),
            'read_at' => optional($this->message->read_at)->toISOString(),
            'seen' => (bool) $this->message->seen,
            'moderation_status' => $this->message->moderation_status ?? 'clean',
            'toxicity_score' => $this->message->toxicity_score ?? 0,
            'created_at' => optional($this->message->created_at)->toISOString(),
        ];
    }

    private function attachments(): array
    {
        if (! $this->message->attachment) {
            return [];
        }

        $ids = array_filter(array_map('trim', explode(',', (string) $this->message->attachment)));

        return array_map(fn (string $id) => [
            'id' => (int) $id,
            'url' => uploaded_asset((int) $id),
        ], $ids);
    }
}
