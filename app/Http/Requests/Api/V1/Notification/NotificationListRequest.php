<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class NotificationListRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'unread_only' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
