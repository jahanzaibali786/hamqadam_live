<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class VerifyMobileOtpRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'size:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', Rule::in(['android', 'ios', 'web', 'unknown'])],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}

