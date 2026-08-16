<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StorePushTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['sometimes', 'nullable', 'string', 'in:ios,android,web'],
            'device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
