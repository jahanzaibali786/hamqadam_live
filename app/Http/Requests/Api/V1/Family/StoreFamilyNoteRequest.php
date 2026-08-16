<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreFamilyNoteRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'profile_user_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
