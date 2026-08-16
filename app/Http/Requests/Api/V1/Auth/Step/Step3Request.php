<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step3Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'about_me' => ['nullable', 'string', 'max:2000'],
            'looking_for' => ['nullable', 'string', 'max:2000'],
            'life_values' => ['nullable', 'array'],
            'life_values.*' => ['string', 'max:100'],
            'personality_type' => ['nullable', 'string', 'max:50'],
            'communication_style' => ['nullable', 'string', 'max:50'],
            'love_language' => ['nullable', 'array'],
            'love_language.*' => ['string', 'max:100'],
            'conflict_resolution_style' => ['nullable', 'string', 'max:50'],
        ];
    }
}
