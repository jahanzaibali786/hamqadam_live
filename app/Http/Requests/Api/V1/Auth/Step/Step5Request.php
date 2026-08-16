<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step5Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'education_level' => ['nullable', 'string', 'max:100'],
            'degree' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'education_start' => ['nullable', 'numeric'],
            'employment_status' => ['nullable', 'string', 'max:20'],
            'profession' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'career_start' => ['nullable', 'numeric'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
            'work_location_city' => ['nullable', 'string', 'max:255'],
        ];
    }
}
