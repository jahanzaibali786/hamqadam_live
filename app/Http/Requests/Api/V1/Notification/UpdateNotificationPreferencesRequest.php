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
        ];
    }
}
