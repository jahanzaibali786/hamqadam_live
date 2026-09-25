<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class GuardianActionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'profile_user_id' => ['required', 'integer', 'exists:users,id'],
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'feedback_type' => ['sometimes', 'string', 'in:not_suitable,recommend'],
            'reason' => ['sometimes', 'string', 'max:60', 'in:location,family_expectations,education,lifestyle,career,religion_culture,other'],
            'comment' => ['sometimes', 'string', 'max:1000'],
            'visibility' => ['sometimes', 'string', 'in:guardian_only,primary_and_guardian,family_context'],
        ];
    }
}
