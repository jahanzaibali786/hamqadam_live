<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Safety;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ReportUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:2000'],
            'severity' => ['sometimes', 'nullable', 'string', 'in:low,medium,high,critical'],
            'evidence' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
