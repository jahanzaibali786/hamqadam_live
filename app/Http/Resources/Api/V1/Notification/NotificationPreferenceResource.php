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
            'quiet_hours_start' => $this->quiet_hours_start,
            'quiet_hours_end' => $this->quiet_hours_end,
            'timezone' => $this->timezone ?: config('app.timezone'),
            'digest_frequency' => $this->digest_frequency ?: 'instant',
            'reminder_interval_minutes' => (int) ($this->reminder_interval_minutes ?: 1440),
            'reminder_max_attempts' => (int) ($this->reminder_max_attempts ?? 3),
            'engagement_enabled' => (bool) ($this->engagement_enabled ?? true),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
