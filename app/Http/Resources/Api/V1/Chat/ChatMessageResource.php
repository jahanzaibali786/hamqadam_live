<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Chat;

use App\Enums\ChatMessageType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->message_type instanceof ChatMessageType ? $this->message_type->value : ($this->message_type ?? 'text');

        return [
            'id' => $this->id,
            'thread_id' => $this->chat_thread_id,
            'sender' => $this->whenLoaded('sender', fn () => new ChatUserResource($this->sender)),
            'message' => $this->message,
            'message_type' => $type,
            'attachments' => $this->attachments(),
            'reply_to' => $this->whenLoaded('replyTo', fn () => $this->replyTo ? new self($this->replyTo) : null),
            'delivered_at' => optional($this->delivered_at)->toISOString(),
            'read_at' => optional($this->read_at)->toISOString(),
            'seen' => (bool) $this->seen,
            'moderation_status' => $this->moderation_status ?? 'clean',
            'toxicity_score' => $this->toxicity_score,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }

    private function attachments(): array
    {
        if (! $this->attachment) {
            return [];
        }

        $ids = array_filter(array_map('trim', explode(',', (string) $this->attachment)));

        return array_map(fn (string $id) => [
            'id' => (int) $id,
            'url' => uploaded_asset((int) $id),
        ], $ids);
    }
}
