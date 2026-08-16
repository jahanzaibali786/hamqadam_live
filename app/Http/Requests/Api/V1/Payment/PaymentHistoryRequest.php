<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Http\Requests\Api\V1\ApiFormRequest;

class PaymentHistoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'in:Due,Paid,pending,paid,failed,cancelled'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
