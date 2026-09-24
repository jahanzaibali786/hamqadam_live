<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ChatListRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            // `archived=1` returns the member's archived tab instead of the
            // inbox; omitted/false keeps the normal, unarchived list.
            'archived' => ['sometimes', 'boolean'],
        ];
    }
}
