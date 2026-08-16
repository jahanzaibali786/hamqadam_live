<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Http\Requests\Api\V1\ApiFormRequest;

class CheckoutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'gateway' => ['required', 'string', 'in:stripe,easypaisa,jazzcash'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'success_url' => ['sometimes', 'nullable', 'url'],
            'cancel_url' => ['sometimes', 'nullable', 'url'],
            'easypaisa_phone' => ['required_if:gateway,easypaisa', 'nullable', 'string', 'max:30'],
            'jazzcash_phone' => ['required_if:gateway,jazzcash', 'nullable', 'string', 'max:30'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
