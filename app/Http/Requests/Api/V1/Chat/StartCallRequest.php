<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StartCallRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'chat_thread_id' => ['required', 'integer', 'exists:chat_threads,id'],
            'call_type' => ['required', 'string', 'in:audio,video'],
        ];
    }
}
