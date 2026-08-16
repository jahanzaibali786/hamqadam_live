<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Content;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreExpertQuestionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:80'],
            'question' => ['required', 'string', 'max:255'],
            'details' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }
}
