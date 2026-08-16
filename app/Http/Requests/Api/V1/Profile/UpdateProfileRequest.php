<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'marital_status_id' => ['sometimes', 'nullable', 'integer'],
            'children' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'on_behalf' => ['sometimes', 'nullable', 'integer'],
            'annual_salary_range_id' => ['sometimes', 'nullable', 'integer'],
            'mother_tongue' => ['sometimes', 'nullable', 'integer'],
            'known_languages' => ['sometimes', 'nullable', 'array'],
            'known_languages.*' => ['integer'],
            'about_me' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'ai_generated_bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'video_introduction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'voice_introduction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'travel_preferences' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'future_goals' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'hide_profile' => ['sometimes', 'boolean'],
        ];
    }
}

