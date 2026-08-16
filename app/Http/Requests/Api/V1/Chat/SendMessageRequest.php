<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SendMessageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['sometimes', 'nullable', 'string', 'max:5000', 'required_without:attachments'],
            'message_type' => ['sometimes', 'nullable', 'string', 'in:text,image,voice,mixed'],
            'reply_to_chat_id' => ['sometimes', 'nullable', 'integer', 'exists:chats,id'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
