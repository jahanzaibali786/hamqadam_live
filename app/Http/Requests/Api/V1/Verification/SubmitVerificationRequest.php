<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Verification;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SubmitVerificationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'cnic_number' => ['required', 'string', 'max:30'],
            'cnic_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'cnic_back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'face' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
