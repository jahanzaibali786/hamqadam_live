<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreGuardianRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'guardian_user_id' => ['required', 'integer', 'exists:users,id'],
            'relationship' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guardian_role' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_wali' => ['sometimes', 'boolean'],
            'digest_frequency' => ['sometimes', 'string', 'in:none,daily,weekly,monthly'],
            'permissions' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
