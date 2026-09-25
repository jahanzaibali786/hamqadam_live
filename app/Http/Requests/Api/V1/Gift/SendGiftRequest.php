<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Gift;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SendGiftRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'gift_id' => ['required', 'integer', 'exists:gifts,id'],
            // NOTE: no `coins` field is accepted — the price comes from the DB.
            'message' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.required' => 'Receiver is required.',
            'gift_id.required' => 'Please select a gift.',
        ];
    }
}
