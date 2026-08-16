<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Chat;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $otherUser = (int) $this->sender_user_id === (int) $viewer->id ? $this->receiver : $this->sender;
        $lastMessage = $this->whenLoaded('chats', fn () => $this->chats->last());

        return [
            'id' => $this->id,
            'thread_code' => $this->thread_code,
            'other_user' => $otherUser ? new ChatUserResource($otherUser) : null,
            'blocked_by_user' => $this->blocked_by_user,
            'message_request_status' => $this->message_request_status ?? 'accepted',
            'unread_count' => Chat::where('chat_thread_id', $this->id)
                ->where('sender_user_id', '!=', $viewer->id)
                ->where('seen', 0)
                ->count(),
            'last_message' => $lastMessage instanceof Chat ? new ChatMessageResource($lastMessage) : null,
            'last_message_at' => optional($this->last_message_at ?? $this->updated_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
