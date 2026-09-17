<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Http\Requests\Api\V1\ApiFormRequest;

class CheckoutRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('custom_coins')) {
            $this->merge(['custom_coins' => (int) filter_var($this->input('custom_coins'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        return [
            'custom_coins' => ['sometimes', 'boolean'],
            'coins' => ['required_if:custom_coins,1', 'prohibited_unless:custom_coins,1', 'integer', 'min:1', 'max:1000000'],
            'package_id' => ['required_unless:custom_coins,1', 'prohibited_if:custom_coins,1', 'integer', 'exists:packages,id', 'exclude_with:custom_coins'],
            'gateway_id' => ['sometimes', 'nullable', 'integer', 'in:1,2,3', 'required_without:gateway'],
            'gateway' => ['sometimes', 'nullable', 'string', 'in:stripe,easypaisa,jazzcash', 'required_without:gateway_id'],
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
