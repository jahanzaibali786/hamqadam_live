<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step4Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'religion_id' => ['sometimes', 'integer'],
            'caste_id' => ['nullable', 'integer'],
            'sub_caste_id' => ['nullable', 'integer'],
            'personal_value' => ['nullable', 'string', 'max:100'],
            'religious_practice_level' => ['nullable', 'string', 'max:50'],
            'prayer_frequency' => ['nullable', 'string', 'max:50'],
            'community_biradari' => ['nullable', 'string', 'max:100'],
            'hijab_beard_preference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
