<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreFamilyApprovalRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'guardian_user_id' => ['required', 'integer', 'exists:users,id'],
            'request_type' => ['required', 'string', 'max:100'],
            'payload' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
