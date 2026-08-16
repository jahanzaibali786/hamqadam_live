<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;

class VerifyRegistrationOtpRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
