<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step10Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'partner_age_min' => ['nullable', 'integer', 'min:18', 'max:100'],
            'partner_age_max' => ['nullable', 'integer', 'min:18', 'max:100'],
            'partner_height_min' => ['nullable', 'numeric', 'between:0,9.99'],
            'partner_height_max' => ['nullable', 'numeric', 'between:0,9.99'],
            'partner_marital_status_id' => ['nullable', 'integer'],
            'partner_religion_id' => ['nullable', 'integer'],
            'partner_caste_id' => ['nullable', 'integer'],
            'partner_sub_caste_id' => ['nullable', 'integer'],
            'partner_education' => ['nullable', 'string', 'max:255'],
            'partner_profession' => ['nullable', 'string', 'max:255'],
            'partner_income_min' => ['nullable', 'numeric', 'min:0'],
            'partner_income_max' => ['nullable', 'numeric', 'min:0'],
            'partner_language_id' => ['nullable', 'integer'],
            'partner_language_ids' => ['nullable', 'array'],
            'partner_language_ids.*' => ['integer'],
            'partner_country_id' => ['nullable', 'integer'],
            'partner_state_id' => ['nullable', 'integer'],
            'partner_city_id' => ['nullable', 'integer'],
            'partner_lifestyle' => ['nullable', 'string', 'max:100'],
            'partner_prayer' => ['nullable', 'string', 'max:100'],
            'partner_religious_practice' => ['nullable', 'string', 'max:100'],
            'partner_body_type' => ['nullable', 'string', 'max:50'],
            'partner_complexion' => ['nullable', 'string', 'max:50'],
            'partner_children_preference' => ['nullable', 'string', 'max:50'],
            'partner_children_acceptable' => ['nullable', 'string', 'max:20'],
            'partner_smoking_acceptable' => ['nullable', 'string', 'max:20'],
            'partner_drinking_acceptable' => ['nullable', 'string', 'max:20'],
            'partner_diet' => ['nullable', 'string', 'max:50'],
            'partner_personal_value' => ['nullable', 'string', 'max:100'],
            'partner_family_value_id' => ['nullable', 'integer'],
            'partner_general' => ['nullable', 'string', 'max:2000'],
            'deal_breakers' => ['nullable', 'array'],
            'deal_breakers.*' => ['string', 'max:255'],
        ];
    }
}
