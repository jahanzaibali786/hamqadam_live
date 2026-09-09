<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\HelpChat;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The member's Help Center conversation as the app sees it.
 */
class HelpChatThreadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'status' => (string) $this->status,
            'is_closed' => $this->status === \App\Models\HelpChatThread::STATUS_CLOSED,
            'unread_count' => (int) $this->user_unread_count,
            'last_message_at' => optional($this->last_message_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
