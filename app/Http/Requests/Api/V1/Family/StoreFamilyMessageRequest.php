<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreFamilyMessageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'attachments' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
