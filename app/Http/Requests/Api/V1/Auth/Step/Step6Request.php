<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step6Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'family_type' => ['nullable', 'string', 'max:50'],
            'siblings_brothers' => ['nullable', 'integer', 'min:0', 'max:50'],
            'siblings_sisters' => ['nullable', 'integer', 'min:0', 'max:50'],
            'married_siblings' => ['nullable', 'integer', 'min:0', 'max:50'],
            'family_location' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:100'],
            'family_values' => ['nullable', 'string', 'max:50'],
            'family_bio' => ['nullable', 'string', 'max:2000'],
            'family_expectations' => ['nullable', 'string', 'max:2000'],
            'parents_contact' => ['nullable', 'string', 'max:100'],
        ];
    }
}
