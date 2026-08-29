<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Enums\PaymentGateway;
use App\Models\PackageUsage;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Payment\CheckoutRequest;
use App\Http\Requests\Api\V1\Payment\CouponValidationRequest;
use App\Http\Requests\Api\V1\Payment\PaymentHistoryRequest;
use App\Http\Requests\Api\V1\Payment\PaymentWebhookRequest;
use App\Http\Resources\Api\V1\Payment\PackageUsageResource;
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

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $member = $user->member;
        $package = $this->payments->current($user);

        return $this->success([
            'current_package' => $package ? new PlanResource($package) : null,
            'package_validity' => $member?->package_validity ? \Carbon\Carbon::parse($member->package_validity)->toDateString() : null,
            'is_active' => (bool) ($member && $member->current_package_id && package_validity($user->id)),
            'remaining' => [
                'coins' => (int) ($member?->remaining_interest ?? 0),
                'contact_view' => (int) ($member?->remaining_contact_view ?? 0),
                'profile_viewer_view' => (int) ($member?->remaining_profile_viewer_view ?? 0),
                'profile_image_view' => (int) ($member?->remaining_profile_image_view ?? 0),
                'gallery_image_view' => (int) ($member?->remaining_gallery_image_view ?? 0),
                'photo_gallery' => (int) ($member?->remaining_photo_gallery ?? 0),
            ],
        ]);
    }

    public function gateways(Request $request): JsonResponse
    {
        $gateways = $this->payments->gateways();

        return $this->success([
            'gateways' => $gateways,
            'summary' => [
                'total_supported' => count($gateways),
                'enabled_gateways' => count(array_filter($gateways, fn ($gateway) => $gateway['enabled'])),
                'available_gateways' => count(array_filter($gateways, fn ($gateway) => $gateway['available'])),
            ],
        ], 'Payment gateways fetched successfully.');
    }

    public function gateway(Request $request, int $gateway): JsonResponse
    {
        return $this->success(
            $this->payments->gatewayDetails(\App\Enums\PaymentGateway::fromId($gateway)),
            'Payment gateway fetched successfully.'
        );
    }

    public function package(Request $request, int $package): JsonResponse
    {
        return $this->success(new PlanResource($this->payments->packageDetails($package)));
    }

    public function usage(Request $request): JsonResponse
    {
        $items = $this->payments->usage($request->user(), ['per_page' => $request->integer('per_page', 20), 'feature' => $request->string('feature')->toString()]);

        return PackageUsageResource::collection($items)->additional([
            'success' => true,
            'summary' => [
                'purchased_coins' => (int) ($request->user()->member?->package?->express_interest ?? 0),
                'used_coins' => (int) PackageUsage::where('user_id', $request->user()->id)->sum('amount'),
                'remaining_coins' => (int) ($request->user()->member?->remaining_interest ?? 0),
            ],
        ])->response();
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $checkout = $this->payments->checkout($request->user(), $request->validated());

        return $this->success([
            'payment' => new PaymentResource($checkout['payment']),
            'gateway' => $checkout['gateway'],
            'gateway_id' => $checkout['gateway_id'],
            'checkout' => $checkout['checkout'],
            'security' => $checkout['security'],
        ], 'Checkout created successfully.', 201);
    }

    public function checkoutStatus(Request $request, int $payment): JsonResponse
    {
        $status = $this->payments->checkoutStatus($request->user(), $payment, $request->string('checkout_token')->toString() ?: null);

        return $this->success([
            'payment' => new PaymentResource($status['payment']),
            'checkout' => $status['checkout'],
        ], 'Checkout status fetched successfully.');
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
