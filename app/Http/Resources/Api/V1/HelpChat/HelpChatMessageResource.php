<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\HelpChat;

use App\Models\HelpChatThread;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One Help Center message. The attachment shape matches ChatMessageSent's,
 * so the app parses both chats with one model.
 */
class HelpChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        // whenLoaded() returns a MissingValue when the relation is not eager
        // loaded, and that object is truthy — so test the relation explicitly.
        $sender = $this->relationLoaded('sender') ? $this->sender : null;
        // Only the member and support write in a help thread, so the sender's
        // user type is the whole test — no need for the thread relation.
        $fromAdmin = $sender && in_array($sender->user_type, ['admin', 'staff', 'subadmin'], true);

        return [
            'id' => (int) $this->id,
            'thread_id' => (int) $this->thread_id,
            'sender_id' => (int) $this->sender_user_id,
            'sender_name' => $sender ? trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '')) : null,
            'sender_photo' => $sender && $sender->photo ? uploaded_asset($sender->photo) : null,
            'from_admin' => $fromAdmin,
            'message' => (string) ($this->message ?? ''),
            'message_type' => (string) ($this->message_type ?? 'text'),
            'attachments' => $this->attachments(),
            'seen' => (bool) $this->seen,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }

    /**
     * Same attachment shape the chat broadcasts use, so one parser serves both.
     */
    private function attachments(): array
    {
        $ids = $this->resource->attachmentIds();
        if ($ids === []) {
            return [];
        }

        $attachments = [];

        foreach ($ids as $id) {
            $upload = \App\Models\Upload::find($id);

            if (! $upload) {
                $attachments[] = [
                    'id' => $id,
                    'type' => 'file',
                    'url' => uploaded_asset($id),
                    'download_url' => route('download_attachment', $id),
                    'name' => 'Attachment',
                    'original_name' => 'Attachment',
                    'extension' => '',
                    'size' => null,
                    'preview_url' => uploaded_asset($id),
                ];

                continue;
            }

            $attachments[] = [
                'id' => (int) $upload->id,
                'type' => (string) $upload->type,
                'url' => uploaded_asset((int) $upload->id),
                'download_url' => route('download_attachment', (int) $upload->id),
                'name' => (string) $upload->file_name,
                'original_name' => (string) $upload->file_original_name,
                'extension' => (string) $upload->extension,
                'size' => $upload->file_size !== null ? (int) $upload->file_size : null,
                'preview_url' => static_asset($upload->file_name),
            ];
        }

        return $attachments;
    }
}
