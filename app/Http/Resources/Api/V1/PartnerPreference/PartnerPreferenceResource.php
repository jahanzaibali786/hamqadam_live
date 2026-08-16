<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\PartnerPreference;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'general' => $this->general,
            'age' => [
                'min' => $this->preferred_age_min,
                'max' => $this->preferred_age_max,
            ],
            'height' => [
                'preferred' => $this->height,
                'min' => $this->height_min,
                'max' => $this->height_max,
            ],
            'weight' => $this->weight,
            'education' => $this->education,
            'profession' => $this->profession,
            'income' => [
                'min' => $this->income_min,
                'max' => $this->income_max,
            ],
            'religion_id' => $this->religion_id,
            'caste_id' => $this->caste_id,
            'sub_caste_id' => $this->sub_caste_id,
            'marital_status_id' => $this->marital_status_id,
            'children_acceptable' => $this->children_acceptable,
            'children_preference' => $this->children_preference,
            'country_id' => $this->preferred_country_id,
            'state_id' => $this->preferred_state_id,
            'city_id' => $this->preferred_city_id,
            'residence_country_id' => $this->residence_country_id,
            'language_id' => $this->language_id,
            'preferred_language_ids' => $this->preferred_language_ids ?: [],
            'lifestyle' => $this->lifestyle,
            'diet' => $this->diet,
            'prayer' => $this->prayer,
            'religious_practice' => $this->religious_practice,
            'smoking_acceptable' => $this->smoking_acceptable,
            'drinking_acceptable' => $this->drinking_acceptable,
            'body_type' => $this->body_type,
            'personal_value' => $this->personal_value,
            'family_value_id' => $this->family_value_id,
            'complexion' => $this->complexion,
            'deal_breakers' => $this->deal_breakers ?: [],
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

