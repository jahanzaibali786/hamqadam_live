<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreFamilyConversationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'proposal_id' => ['sometimes', 'nullable', 'integer', 'exists:express_interests,id'],
            'profile_user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
