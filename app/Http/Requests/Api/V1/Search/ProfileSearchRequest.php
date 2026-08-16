<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Search;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ProfileSearchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'age_min' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'age_max' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100', 'gte:age_min'],
            'height_min' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99'],
            'height_max' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99', 'gte:height_min'],
            'religion_id' => ['sometimes', 'nullable', 'integer'],
            'sect_id' => ['sometimes', 'nullable', 'integer'],
            'caste_id' => ['sometimes', 'nullable', 'integer'],
            'sub_caste_id' => ['sometimes', 'nullable', 'integer'],
            'education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'income_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'income_max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:income_min'],
            'lifestyle' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country_id' => ['sometimes', 'nullable', 'integer'],
            'state_id' => ['sometimes', 'nullable', 'integer'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'nearby' => ['sometimes', 'boolean'],
            'international' => ['sometimes', 'boolean'],
            'language_id' => ['sometimes', 'nullable', 'integer'],
            'verified_only' => ['sometimes', 'boolean'],
            'photo_only' => ['sometimes', 'boolean'],
            'recently_active' => ['sometimes', 'boolean'],
            'online_now' => ['sometimes', 'boolean'],
            'new_profiles' => ['sometimes', 'boolean'],
            'new_this_week' => ['sometimes', 'boolean'],
            'exclude_viewed' => ['sometimes', 'boolean'],
            'mutual_match' => ['sometimes', 'boolean'],
            'compatibility_min' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:newest,compatibility,recently_active'],
        ];
    }
}
