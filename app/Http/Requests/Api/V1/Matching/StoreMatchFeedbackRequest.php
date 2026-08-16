<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Matching;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreMatchFeedbackRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'feedback' => ['required', 'string', 'in:up,down,like,pass,super_like'],
            'source' => ['sometimes', 'nullable', 'string', 'max:100'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
