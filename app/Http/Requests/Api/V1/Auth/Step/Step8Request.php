<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step8Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'diet' => ['nullable', 'string', 'max:50'],
            'smoke' => ['nullable', 'string', 'max:20'],
            'drink' => ['nullable', 'string', 'max:20'],
            'living_with' => ['nullable', 'string', 'max:100'],
            'hobbies' => ['nullable', 'string', 'max:2000'],
            'interests_multi_select' => ['nullable', 'array'],
            'interests_multi_select.*' => ['string', 'max:100'],
            'travel_preferences' => ['nullable', 'string', 'max:1000'],
            'future_goals' => ['nullable', 'string', 'max:1000'],
            'health_conditions' => ['nullable', 'string', 'max:1000'],
            'languages_spoken_fluently' => ['nullable', 'array'],
            'languages_spoken_fluently.*' => ['string', 'max:100'],
            'favorite_weekend_activities' => ['nullable', 'string', 'max:255'],
            'proposal_preferences' => ['nullable', 'string', 'max:100'],
            'communication_preferences' => ['nullable', 'array'],
            'communication_preferences.*' => ['string', 'max:50'],
        ];
    }
}
