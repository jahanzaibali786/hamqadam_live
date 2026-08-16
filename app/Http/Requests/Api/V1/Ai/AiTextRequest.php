<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Ai;

use App\Http\Requests\Api\V1\ApiFormRequest;

class AiTextRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'context' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
