<?php

declare(strict_types=1);

namespace App\Services\Api\V1\PartnerPreference;

use App\Models\PartnerExpectation;
use App\Models\User;

class PartnerPreferenceService
{
    public function get(User $user): PartnerExpectation
    {
        return PartnerExpectation::firstOrCreate(['user_id' => $user->id]);
    }

    public function update(User $user, array $data): PartnerExpectation
    {
        $preference = $this->get($user);
        $preference->fill($data);
        $preference->save();

        return $preference->fresh();
    }

    public function clear(User $user): PartnerExpectation
    {
        $preference = $this->get($user);

        $preference->fill([
            'general' => null,
            'preferred_age_min' => null,
            'preferred_age_max' => null,
            'height' => null,
            'height_min' => null,
            'height_max' => null,
            'weight' => null,
            'marital_status_id' => null,
            'children_acceptable' => null,
            'children_preference' => null,
            'residence_country_id' => null,
            'religion_id' => null,
            'caste_id' => null,
            'sub_caste_id' => null,
            'education' => null,
            'profession' => null,
            'income_min' => null,
            'income_max' => null,
            'smoking_acceptable' => null,
            'drinking_acceptable' => null,
            'diet' => null,
            'lifestyle' => null,
            'prayer' => null,
            'religious_practice' => null,
            'body_type' => null,
            'personal_value' => null,
            'manglik' => null,
            'language_id' => null,
            'preferred_language_ids' => null,
            'family_value_id' => null,
            'preferred_country_id' => null,
            'preferred_state_id' => null,
            'preferred_city_id' => null,
            'complexion' => null,
            'deal_breakers' => null,
        ]);
        $preference->save();

        return $preference->fresh();
    }
}

