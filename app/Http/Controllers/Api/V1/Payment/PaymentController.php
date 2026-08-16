<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Enums\PaymentGateway;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Payment\CheckoutRequest;
use App\Http\Requests\Api\V1\Payment\CouponValidationRequest;
use App\Http\Requests\Api\V1\Payment\PaymentHistoryRequest;
use App\Http\Requests\Api\V1\Payment\PaymentWebhookRequest;
use App\Http\Resources\Api\V1\Payment\PaymentResource;
use App\Http\Resources\Api\V1\Payment\PlanResource;
use App\Services\Api\V1\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function plans(): JsonResponse
    {
        return $this->success(PlanResource::collection($this->payments->plans()));
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $checkout = $this->payments->checkout($request->user(), $request->validated());

        return $this->success([
            'payment' => new PaymentResource($checkout['payment']),
            'gateway' => $checkout['gateway'],
            'checkout' => $checkout['checkout'],
        ], 'Checkout created successfully.', 201);
    }

    public function history(PaymentHistoryRequest $request): JsonResponse
    {
        return PaymentResource::collection(
            $this->payments->history($request->user(), $request->validated())
        )->additional(['success' => true])->response();
    }

    public function invoice(Request $request, int $payment): JsonResponse
    {
        return $this->success(new PaymentResource($this->payments->invoice($request->user(), $payment)));
    }

    public function validateCoupon(CouponValidationRequest $request): JsonResponse
    {
        return $this->success($this->payments->validateCoupon(
            $request->validated('code'),
            (int) $request->validated('package_id')
        ));
    }

    public function stripeWebhook(PaymentWebhookRequest $request): JsonResponse
    {
        return $this->success(
            new PaymentResource($this->payments->processWebhook(PaymentGateway::Stripe, $request->validated())),
            'Stripe webhook processed.'
        );
    }

    public function easypaisaWebhook(PaymentWebhookRequest $request): JsonResponse
    {
        return $this->success(
            new PaymentResource($this->payments->processWebhook(PaymentGateway::EasyPaisa, $request->validated())),
            'EasyPaisa webhook processed.'
        );
    }

    public function jazzcashWebhook(PaymentWebhookRequest $request): JsonResponse
    {
        return $this->success(
            new PaymentResource($this->payments->processWebhook(PaymentGateway::JazzCash, $request->validated())),
            'JazzCash webhook processed.'
        );
    }
}
