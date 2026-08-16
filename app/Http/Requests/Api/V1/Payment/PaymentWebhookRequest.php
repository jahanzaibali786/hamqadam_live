<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Http\Requests\Api\V1\ApiFormRequest;

class PaymentWebhookRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gateway_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:paid,success,completed,failed,cancelled'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'payload' => ['sometimes', 'array'],
        ];
    }
}
