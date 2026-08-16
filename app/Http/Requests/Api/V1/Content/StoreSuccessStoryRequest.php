<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Content;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreSuccessStoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'story' => ['required', 'string', 'max:5000'],
            'partner_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }
}
