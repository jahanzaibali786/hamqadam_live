<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Search;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreSavedSearchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'filters' => ['required', 'array'],
            'filters.age_min' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'filters.age_max' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100', 'gte:filters.age_min'],
            'filters.height_min' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99'],
            'filters.height_max' => ['sometimes', 'nullable', 'numeric', 'between:0,9.99', 'gte:filters.height_min'],
            'filters.religion_id' => ['sometimes', 'nullable', 'integer'],
            'filters.sect_id' => ['sometimes', 'nullable', 'integer'],
            'filters.caste_id' => ['sometimes', 'nullable', 'integer'],
            'filters.sub_caste_id' => ['sometimes', 'nullable', 'integer'],
            'filters.education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters.profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters.income_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'filters.income_max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:filters.income_min'],
            'filters.lifestyle' => ['sometimes', 'nullable', 'string', 'max:100'],
            'filters.country_id' => ['sometimes', 'nullable', 'integer'],
            'filters.state_id' => ['sometimes', 'nullable', 'integer'],
            'filters.city_id' => ['sometimes', 'nullable', 'integer'],
            'filters.nearby' => ['sometimes', 'boolean'],
            'filters.international' => ['sometimes', 'boolean'],
            'filters.language_id' => ['sometimes', 'nullable', 'integer'],
            'filters.verified_only' => ['sometimes', 'boolean'],
            'filters.photo_only' => ['sometimes', 'boolean'],
            'filters.recently_active' => ['sometimes', 'boolean'],
            'filters.online_now' => ['sometimes', 'boolean'],
            'filters.new_profiles' => ['sometimes', 'boolean'],
            'filters.new_this_week' => ['sometimes', 'boolean'],
            'filters.exclude_viewed' => ['sometimes', 'boolean'],
            'filters.mutual_match' => ['sometimes', 'boolean'],
            'filters.compatibility_min' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'filters.sort' => ['sometimes', 'nullable', 'string', 'in:newest,compatibility,recently_active'],
            'notify' => ['sometimes', 'boolean'],
        ];
    }
}
