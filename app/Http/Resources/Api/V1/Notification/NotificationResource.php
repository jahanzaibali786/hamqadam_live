<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : json_decode((string) $this->data, true);
        $data = is_array($data) ? $data : [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? class_basename($this->type),
            'title' => $data['title'] ?? str_replace('_', ' ', (string) ($data['type'] ?? 'notification')),
            'message' => $data['message'] ?? null,
            'deep_link' => $data['deep_link'] ?? ($data['route'] ?? null),
            'notify_by' => $data['notify_by'] ?? null,
            'info_id' => $data['info_id'] ?? null,
            'payload' => $data,
            'read_at' => optional($this->read_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
