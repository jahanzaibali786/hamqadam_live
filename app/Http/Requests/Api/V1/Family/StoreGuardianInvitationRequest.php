<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreGuardianInvitationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'contact' => ['required_without:guardian_user_id', 'nullable', 'string', 'max:120'],
            'guardian_user_id' => ['required_without:contact', 'nullable', 'integer', 'exists:users,id'],
            'relationship' => ['required', 'string', 'max:60'],
            'guardian_role' => ['sometimes', 'string', 'max:40', 'in:primary,supporting,custom'],
            'is_wali' => ['sometimes', 'boolean'],
            'permission_preset' => ['sometimes', 'string', 'in:view_only,review,participate,custom'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'max:64'],
            'message' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
