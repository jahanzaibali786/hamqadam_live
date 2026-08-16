<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreRelationshipStatusRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'partner_user_id' => ['required', 'integer', 'exists:users,id'],
            'proposal_id' => ['sometimes', 'nullable', 'integer', 'exists:express_interests,id'],
            'status' => ['required', 'string', 'in:engaged,nikah,married,cancelled'],
            'status_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
