<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'invoice_number' => $this->invoice_number,
            'package' => $this->whenLoaded('package', fn () => new PlanResource($this->package)),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'gateway_status' => $this->gateway_status,
            'gateway_reference' => $this->gateway_reference,
            'amount' => (float) $this->amount,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'payable_amount' => (float) ($this->payable_amount ?? $this->amount),
            'currency' => $this->currency ?? 'PKR',
            'paid_at' => optional($this->paid_at)->toISOString(),
            'subscription_ends_at' => optional($this->subscription_ends_at)->toISOString(),
            'metadata' => $this->metadata,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
