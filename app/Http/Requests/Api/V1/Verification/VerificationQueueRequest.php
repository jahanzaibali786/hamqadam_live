<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Verification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class VerificationQueueRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'in:submitted,under_review,approved,rejected'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
