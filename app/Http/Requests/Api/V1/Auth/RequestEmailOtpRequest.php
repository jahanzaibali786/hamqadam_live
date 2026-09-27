<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * POST /auth/otp/email — QA requirement: the backend must support EMAIL OTP
 * login/account recovery, replacing the previous mobile-OTP dependency.
 */
class RequestEmailOtpRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
