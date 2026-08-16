<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step7Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'children_preference' => ['nullable', 'string', 'max:50'],
            'relocation_preference' => ['nullable', 'string', 'max:50'],
            'visa_immigration_status' => ['nullable', 'string', 'max:50'],
            'future_living_preference' => ['nullable', 'string', 'max:50'],
            'financial_responsibility' => ['nullable', 'string', 'max:50'],
            'marriage_timeline' => ['nullable', 'string', 'max:50'],
            'expectations_after_marriage' => ['nullable', 'array'],
            'expectations_after_marriage.*' => ['string', 'max:100'],
            'willing_to_work_after_marriage' => ['nullable', 'boolean'],
            'expects_spouse_to_work' => ['nullable', 'boolean'],
        ];
    }
}
