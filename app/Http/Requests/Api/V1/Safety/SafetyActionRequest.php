<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Safety;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SafetyActionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
