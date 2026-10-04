<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateNotificationPreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'in_app_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'event_preferences' => ['sometimes', 'nullable', 'array'],
            'quiet_hours_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'date_format:H:i'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'digest_frequency' => ['sometimes', 'in:instant,daily,weekly,off'],
            'reminder_interval_minutes' => ['sometimes', 'integer', 'between:60,10080'],
            'reminder_max_attempts' => ['sometimes', 'integer', 'between:0,10'],
            'engagement_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
