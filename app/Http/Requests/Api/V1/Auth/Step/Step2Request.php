<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step2Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'gender' => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'marital_status_id' => ['sometimes', 'integer'],
            'children' => ['nullable', 'string', 'max:50'],
            'mother_tongue' => ['sometimes', 'integer'],
            'known_languages' => ['nullable', 'array'],
            'known_languages.*' => ['integer'],
            'height' => ['nullable', 'numeric', 'between:0,9.99'],
            'weight' => ['nullable', 'numeric', 'between:0,999.99'],
            'disability' => ['nullable', 'string', 'max:255'],
            'country_id' => ['sometimes', 'integer'],
            'state_id' => ['sometimes', 'integer'],
            'city_id' => ['sometimes', 'integer'],
        ];
    }
}
