<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'in_app_enabled' => (bool) $this->in_app_enabled,
            'push_enabled' => (bool) $this->push_enabled,
            'email_enabled' => (bool) $this->email_enabled,
            'sms_enabled' => (bool) $this->sms_enabled,
            'event_preferences' => $this->event_preferences ?? [],
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
