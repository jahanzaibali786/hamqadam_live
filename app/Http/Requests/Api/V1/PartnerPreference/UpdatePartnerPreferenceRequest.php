<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\PartnerPreference;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdatePartnerPreferenceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'general' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferred_age_min' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'preferred_age_max' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100', 'gte:preferred_age_min'],
            'height' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99'],
            'height_min' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99'],
            'height_max' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99', 'gte:height_min'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'between:0,999.99'],
            'marital_status_id' => ['sometimes', 'nullable', 'integer'],
            'children_acceptable' => ['sometimes', 'nullable', 'string', 'max:20'],
            'children_preference' => ['sometimes', 'nullable', 'string', 'max:50'],
            'residence_country_id' => ['sometimes', 'nullable', 'integer'],
            'religion_id' => ['sometimes', 'nullable', 'integer'],
            'caste_id' => ['sometimes', 'nullable', 'integer'],
            'sub_caste_id' => ['sometimes', 'nullable', 'integer'],
            'education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:100'],
            'income_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'income_max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:income_min'],
            'smoking_acceptable' => ['sometimes', 'nullable', 'string', 'max:20'],
            'drinking_acceptable' => ['sometimes', 'nullable', 'string', 'max:20'],
            'diet' => ['sometimes', 'nullable', 'string', 'max:50'],
            'lifestyle' => ['sometimes', 'nullable', 'string', 'max:100'],
            'prayer' => ['sometimes', 'nullable', 'string', 'max:100'],
            'religious_practice' => ['sometimes', 'nullable', 'string', 'max:100'],
            'body_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'personal_value' => ['sometimes', 'nullable', 'string', 'max:50'],
            'manglik' => ['sometimes', 'nullable', 'string', 'max:50'],
            'language_id' => ['sometimes', 'nullable', 'integer'],
            'preferred_language_ids' => ['sometimes', 'nullable', 'array'],
            'preferred_language_ids.*' => ['integer'],
            'family_value_id' => ['sometimes', 'nullable', 'integer'],
            'preferred_country_id' => ['sometimes', 'nullable', 'integer'],
            'preferred_state_id' => ['sometimes', 'nullable', 'integer'],
            'preferred_city_id' => ['sometimes', 'nullable', 'integer'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'deal_breakers' => ['sometimes', 'nullable', 'array'],
            'deal_breakers.*' => ['string', 'max:255'],
        ];
    }
}

