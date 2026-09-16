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
            // Voice-note waveform/length lives here (metadata JSON on the chats
            // table) so the app can render a proper player without fetching
            // anything else. Normalized (ints, never strings) because multipart
            // form-data delivers every value as a string.
            'metadata' => $this->normalizedMetadata(),
            // Disappearing message: when this row will vanish (null = never).
            'expires_at' => optional($this->expires_at)->toISOString(),
            'moderation_status' => $this->moderation_status ?? 'clean',
            'toxicity_score' => $this->toxicity_score,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }

    /**
     * Multipart uploads arrive with every field as a string ("duration" => "7",
     * "waveform" => ["3","9"]) which crashes client-side int casts. Store and
     * serve the voice-note fields as proper integers.
     */
    private function normalizedMetadata(): ?array
    {
        $meta = $this->metadata;
        if (is_string($meta) && $meta !== '') {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($meta)) {
            return null;
        }
        if (array_key_exists('duration', $meta) && $meta['duration'] !== null) {
            $meta['duration'] = (int) $meta['duration'];
        }
        if (isset($meta['waveform']) && is_array($meta['waveform'])) {
            $meta['waveform'] = array_map(static fn ($value) => (int) $value, $meta['waveform']);
        }

        return $meta;
    }

    private function attachments(): array
    {
        if (! $this->attachment) {
            return [];
        }

        $ids = array_filter(array_map('trim', explode(',', (string) $this->attachment)));

        return array_map(function (string $id) {
            $upload = \App\Models\Upload::find((int) $id);
            return [
                'id' => (int) $id,
                'url' => uploaded_asset((int) $id),
                // Web-sent files keep richer payloads than the API's own
                // uploads; expose the type so clients can render audio/voice
                // bubbles from any sender.
                'type' => $upload?->type ?? 'file',
                'original_name' => $upload?->file_original_name ?? null,
                'extension' => $upload?->extension ?? null,
                'size' => $upload?->file_size ?? null,
            ];
        }, $ids);
    }
}
