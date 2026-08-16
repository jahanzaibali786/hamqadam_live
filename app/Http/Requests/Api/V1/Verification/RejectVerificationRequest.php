<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Verification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class RejectVerificationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
