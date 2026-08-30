<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Payment;

use App\Enums\ApiErrorCode;
use App\Enums\PaymentGateway;
use App\Exceptions\ApiException;
use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\PackageUsage;
use App\Models\PaymentCoupon;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function plans()
    {
        return Package::where('active', 1)->orderBy('price')->get();
    }

    public function current(User $user): ?Package
    {
        return $user->member?->current_package_id ? Package::find($user->member->current_package_id) : null;
    }

    public function packageDetails(int $packageId): Package
    {
        return $this->activePackage($packageId);
    }

    public function usage(User $user, array $filters): LengthAwarePaginator
    {
        $query = PackageUsage::where('user_id', $user->id)->latest();

        if (! empty($filters['feature'])) {
            $query->where('feature', $filters['feature']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function history(User $user, array $filters): LengthAwarePaginator
    {
        $query = PackagePayment::with('package')
            ->where('user_id', $user->id)
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('payment_status', $this->normalizeStatus($filters['status']));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function invoice(User $user, int $paymentId): PackagePayment
    {
        $payment = PackagePayment::with(['package', 'coupon'])
            ->where('user_id', $user->id)
            ->find($paymentId);

        if (! $payment) {
            throw new ApiException('Invoice not found.', 404, ApiErrorCode::NotFound->value);
        }

        return $payment;
    }

    public function validateCoupon(string $code, int $packageId): array
    {
        $package = $this->activePackage($packageId);
        $coupon = $this->couponByCode($code);
        $discount = $this->discountFor($coupon, (float) $package->price);

        return [
            'valid' => true,
            'code' => $coupon->code,
            'discount_amount' => $discount,
            'payable_amount' => max(0, (float) $package->price - $discount),
        ];
    }

    public function checkout(User $user, array $data): array
    {
        $package = $this->activePackage((int) $data['package_id']);
        $coupon = ! empty($data['coupon_code']) ? $this->couponByCode($data['coupon_code']) : null;
        $discount = $coupon ? $this->discountFor($coupon, (float) $package->price) : 0.0;
        $payableAmount = max(0, (float) $package->price - $discount);
        $gateway = $this->resolveGateway($data);
        $this->assertGatewayAvailable($gateway);
        $reference = $gateway->value . '_' . Str::uuid()->toString();
        $checkoutToken = Str::random(64);
        $checkoutTokenHash = hash_hmac('sha256', $checkoutToken, config('app.key'));
        $checkoutTokenExpiresAt = now()->addMinutes(15);

        return DB::transaction(function () use ($user, $package, $coupon, $discount, $payableAmount, $gateway, $reference, $data, $checkoutToken, $checkoutTokenHash, $checkoutTokenExpiresAt) {
            $payment = PackagePayment::create([
                'payment_code' => now()->format('ymd-His') . '-' . random_int(1000, 9999),
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method' => $gateway->value,
                'payment_status' => 'Due',
                'amount' => $package->price,
                'discount_amount' => $discount,
                'payable_amount' => $payableAmount,
                'currency' => strtoupper($data['currency'] ?? 'PKR'),
                'coupon_id' => $coupon?->id,
                'gateway_reference' => $reference,
                'gateway_status' => 'pending',
                'offline_payment' => in_array($gateway, [PaymentGateway::EasyPaisa, PaymentGateway::JazzCash], true) ? 1 : 2,
                'metadata' => [
                    'success_url' => $data['success_url'] ?? null,
                    'cancel_url' => $data['cancel_url'] ?? null,
                    'easypaisa_phone' => $data['easypaisa_phone'] ?? null,
                    'jazzcash_phone' => $data['jazzcash_phone'] ?? null,
                    'client_metadata' => $data['metadata'] ?? [],
                    'checkout_token_hash' => $checkoutTokenHash,
                    'checkout_token_expires_at' => $checkoutTokenExpiresAt->toISOString(),
                ],
            ]);

            if ($payableAmount <= 0) {
                $this->markPaid($payment, ['source' => 'coupon_full_discount']);
            }

            return [
                'payment' => $payment->fresh(['package', 'coupon']),
                'gateway' => $gateway->value,
                'gateway_id' => $gateway->id(),
                'checkout' => $this->checkoutInstructions($gateway, $payment),
                'security' => [
                    'checkout_token' => $checkoutToken,
                    'checkout_token_expires_at' => $checkoutTokenExpiresAt->toISOString(),
                    'status_endpoint' => url('/api/v1/payments/checkout/' . $payment->id . '/status'),
                    'payment_id' => $payment->id,
                ],
            ];
        });
    }


    public function checkoutStatus(User $user, int $paymentId, ?string $checkoutToken = null): array
    {
        $payment = PackagePayment::with(['package', 'coupon'])
            ->where('user_id', $user->id)
            ->find($paymentId);

        if (! $payment) {
            throw new ApiException('Payment record not found.', 404, ApiErrorCode::NotFound->value);
        }

        $metadata = (array) ($payment->metadata ?? []);
        $storedHash = $metadata['checkout_token_hash'] ?? null;
        $expiresAt = $metadata['checkout_token_expires_at'] ?? null;

        if ($checkoutToken && $storedHash) {
            $incomingHash = hash_hmac('sha256', $checkoutToken, config('app.key'));
            if (! hash_equals((string) $storedHash, $incomingHash)) {
                throw new ApiException('Checkout token mismatch.', 422, ApiErrorCode::ValidationFailed->value);
            }
        }

        return [
            'payment' => $payment,
            'checkout' => [
                'payment_id' => $payment->id,
                'payment_code' => $payment->payment_code,
                'gateway' => $payment->payment_method,
                'gateway_id' => PaymentGateway::from($payment->payment_method)->id(),
                'status' => $payment->payment_status,
                'gateway_status' => $payment->gateway_status,
                'paid_at' => optional($payment->paid_at)->toISOString(),
                'subscription_ends_at' => optional($payment->subscription_ends_at)->toISOString(),
                'checkout_token_expires_at' => $expiresAt,
                'can_refresh' => in_array($payment->payment_status, ['Due', 'Failed', 'Cancelled'], true),
            ],
        ];
    }

    public function processWebhook(PaymentGateway $gateway, array $data): PackagePayment
    {
        return DB::transaction(function () use ($gateway, $data) {
            PaymentWebhookEvent::firstOrCreate([
                'gateway' => $gateway->value,
                'event_id' => $data['event_id'] ?? ($data['gateway_reference'] ?? null),
            ], [
                'event_type' => $data['event_type'] ?? 'payment.updated',
                'payload' => $data,
                'processed_at' => now(),
            ]);

            $payment = PackagePayment::where('payment_method', $gateway->value)
                ->when(! empty($data['payment_code']), fn ($query) => $query->where('payment_code', $data['payment_code']))
                ->when(empty($data['payment_code']) && ! empty($data['gateway_reference']), fn ($query) => $query->where('gateway_reference', $data['gateway_reference']))
                ->first();

            if (! $payment) {
                throw new ApiException('Payment record not found for webhook.', 404, ApiErrorCode::NotFound->value);
            }

            if (in_array($data['status'], ['paid', 'success', 'completed'], true)) {
                return $this->markPaid($payment, $data)->fresh(['package', 'coupon']);
            }

            $payment->forceFill([
                'payment_status' => $data['status'] === 'cancelled' ? 'Cancelled' : 'Failed',
                'gateway_status' => $data['status'],
                'payment_details' => json_encode($data),
            ])->save();

            return $payment->fresh(['package', 'coupon']);
        });
    }

    private function markPaid(PackagePayment $payment, array $details): PackagePayment
    {
        if ($payment->payment_status === 'Paid') {
            return $payment;
        }

        $package = Package::findOrFail($payment->package_id);
        $member = Member::where('user_id', $payment->user_id)->lockForUpdate()->first();
        $user = User::findOrFail($payment->user_id);

        if (! $member) {
            throw new ApiException('Member profile not found for payment activation.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $member->current_package_id = $package->id;
        $member->remaining_interest += (int) $package->express_interest;
        $member->remaining_photo_gallery += (int) $package->photo_gallery;
        $member->remaining_contact_view += (int) $package->contact;
        $member->remaining_profile_viewer_view += (int) $package->profile_viewers_view;
        $member->remaining_profile_image_view += (int) $package->profile_image_view;
        $member->remaining_gallery_image_view += (int) $package->gallery_image_view;
        $member->auto_profile_match = $package->auto_profile_match;
        $member->auto_horoscope_profile_match = $package->auto_horoscope_profile_match;
        $member->package_validity = Carbon::now()->addDays((int) $package->validity)->format('Y-m-d');
        $member->save();

        $user->membership = (int) $package->id === 1 ? 1 : 2;
        $user->save();

        if ($payment->coupon_id) {
            PaymentCoupon::whereKey($payment->coupon_id)->increment('used_count');
        }

        $payment->forceFill([
            'payment_status' => 'Paid',
            'gateway_status' => 'paid',
            'paid_at' => now(),
            'subscription_ends_at' => Carbon::parse($member->package_validity)->endOfDay(),
            'payment_details' => json_encode($details),
        ])->save();

        return $payment;
    }


    public function gateways(): array
    {
        return array_map(
            fn (PaymentGateway $gateway) => $this->gatewayDefinition($gateway),
            PaymentGateway::cases()
        );
    }

    private function gatewayDefinition(PaymentGateway $gateway): array
    {
        return match ($gateway) {
            PaymentGateway::Stripe => [
                'id' => $gateway->id(),
                'key' => $gateway->value,
                'name' => 'Stripe',
                'label' => 'Stripe Card Payments',
                'description' => 'Card and online payments via Stripe checkout.',
                'payment_method_activation_key' => 'stripe_payment_activation',
                'enabled' => get_setting('stripe_payment_activation') == 1,
                'configured' => filled($this->setting('STRIPE_KEY')) && filled($this->setting('STRIPE_SECRET')),
                'available' => get_setting('stripe_payment_activation') == 1
                    && filled($this->setting('STRIPE_KEY'))
                    && filled($this->setting('STRIPE_SECRET')),
                'sandbox' => false,
                'mode' => 'online',
                'image' => static_asset('assets/img/payment_method/stripe.png'),
                'checkout_type' => 'stripe_checkout',
                'webhook_endpoint' => url('/api/v1/payments/webhooks/stripe'),
                'required_settings' => ['STRIPE_KEY', 'STRIPE_SECRET'],
                'settings' => [
                    'publishable_key' => $this->maskValue($this->setting('STRIPE_KEY')),
                    'secret_key_configured' => filled($this->setting('STRIPE_SECRET')),
                ],
                'instructions' => null,
                'note' => 'Use the publishable key on the client and keep the secret key server-side only.',
            ],
            PaymentGateway::EasyPaisa => [
                'id' => $gateway->id(),
                'key' => $gateway->value,
                'name' => 'EasyPaisa',
                'label' => 'EasyPaisa Wallet Payment',
                'description' => 'Pakistan wallet payment flow configured from admin settings.',
                'payment_method_activation_key' => 'easypaisa_payment_activation',
                'enabled' => get_setting('easypaisa_payment_activation') == 1,
                'configured' => filled($this->setting('EASYPAISA_STORE_ID')) && filled($this->setting('EASYPAISA_ACCOUNT_MSISDN')),
                'available' => get_setting('easypaisa_payment_activation') == 1
                    && filled($this->setting('EASYPAISA_STORE_ID'))
                    && filled($this->setting('EASYPAISA_ACCOUNT_MSISDN')),
                'sandbox' => get_setting('easypaisa_sandbox') == 1,
                'mode' => 'manual_gateway_confirmation',
                'image' => static_asset('assets/img/payment_method/easypaisa.png'),
                'checkout_type' => 'manual_gateway_confirmation',
                'webhook_endpoint' => url('/api/v1/payments/webhooks/easypaisa'),
                'required_settings' => ['EASYPAISA_STORE_ID', 'EASYPAISA_ACCOUNT_MSISDN'],
                'settings' => [
                    'store_id' => $this->maskValue($this->setting('EASYPAISA_STORE_ID')),
                    'account_msisdn' => $this->maskValue($this->setting('EASYPAISA_ACCOUNT_MSISDN')),
                    'username' => $this->maskValue($this->setting('EASYPAISA_USERNAME')),
                    'endpoint' => $this->setting('EASYPAISA_ENDPOINT'),
                    'has_password' => filled($this->setting('EASYPAISA_PASSWORD')),
                    'has_hash_key' => filled($this->setting('EASYPAISA_HASH_KEY')),
                ],
                'instructions' => $this->setting('EASYPAISA_PAYMENT_INSTRUCTIONS'),
                'note' => 'This gateway needs admin-configured EasyPaisa credentials and backend callback verification.',
            ],
            PaymentGateway::JazzCash => [
                'id' => $gateway->id(),
                'key' => $gateway->value,
                'name' => 'JazzCash',
                'label' => 'JazzCash Wallet Payment',
                'description' => 'Pakistan wallet payment flow configured from admin settings.',
                'payment_method_activation_key' => 'jazzcash_payment_activation',
                'enabled' => get_setting('jazzcash_payment_activation') == 1,
                'configured' => filled($this->setting('JAZZCASH_MERCHANT_ID')) && filled($this->setting('JAZZCASH_ACCOUNT_MSISDN')),
                'available' => get_setting('jazzcash_payment_activation') == 1
                    && filled($this->setting('JAZZCASH_MERCHANT_ID'))
                    && filled($this->setting('JAZZCASH_ACCOUNT_MSISDN')),
                'sandbox' => get_setting('jazzcash_sandbox') == 1,
                'mode' => 'manual_gateway_confirmation',
                'image' => static_asset('assets/img/payment_method/jazzcash.png'),
                'checkout_type' => 'manual_gateway_confirmation',
                'webhook_endpoint' => url('/api/v1/payments/webhooks/jazzcash'),
                'required_settings' => ['JAZZCASH_MERCHANT_ID', 'JAZZCASH_ACCOUNT_MSISDN'],
                'settings' => [
                    'merchant_id' => $this->maskValue($this->setting('JAZZCASH_MERCHANT_ID')),
                    'account_msisdn' => $this->maskValue($this->setting('JAZZCASH_ACCOUNT_MSISDN')),
                    'endpoint' => $this->setting('JAZZCASH_ENDPOINT'),
                    'has_password' => filled($this->setting('JAZZCASH_PASSWORD')),
                    'has_integrity_salt' => filled($this->setting('JAZZCASH_INTEGRITY_SALT')),
                ],
                'instructions' => $this->setting('JAZZCASH_PAYMENT_INSTRUCTIONS'),
                'note' => 'This gateway needs admin-configured JazzCash credentials and backend callback verification.',
                'request_schema' => [
                    'package_id' => 'integer',
                    'gateway_id' => 'integer',
                    'gateway' => 'string',
                    'currency' => 'string',
                    'success_url' => 'url|null',
                    'cancel_url' => 'url|null',
                    'metadata' => 'array|null',
                    'easypaisa_phone' => 'string|null',
                    'jazzcash_phone' => 'string|null',
                ],
                'sample_payload' => [
                    'stripe' => [
                        'package_id' => 1,
                        'gateway_id' => 1,
                        'currency' => 'PKR',
                    ],
                    'easypaisa' => [
                        'package_id' => 1,
                        'gateway_id' => 2,
                        'currency' => 'PKR',
                        'easypaisa_phone' => '03001234567',
                    ],
                    'jazzcash' => [
                        'package_id' => 1,
                        'gateway_id' => 3,
                        'currency' => 'PKR',
                        'jazzcash_phone' => '03001234567',
                    ],
                ],
            ],
        };
    }

    private function maskValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (strlen($value) <= 4) {
            return $value;
        }

        return str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4);
    }

    private function resolveGateway(array $data): PaymentGateway
    {
        if (! empty($data['gateway'])) {
            return PaymentGateway::from($data['gateway']);
        }

        if (! empty($data['gateway_id'])) {
            return PaymentGateway::fromId((int) $data['gateway_id']);
        }

        throw new ApiException('Payment gateway is required.', 422, ApiErrorCode::ValidationFailed->value);
    }

    public function gatewayDetails(PaymentGateway $gateway): array
    {
        return $this->gatewayDefinition($gateway);
    }

    private function activePackage(int $packageId): Package
    {
        $package = Package::where('active', 1)->find($packageId);
        if (! $package) {
            throw new ApiException('Subscription plan not found.', 404, ApiErrorCode::NotFound->value);
        }

        return $package;
    }

    private function couponByCode(string $code): PaymentCoupon
    {
        $coupon = PaymentCoupon::where('code', strtoupper($code))->first();
        if (! $coupon || ! $coupon->active) {
            throw new ApiException('Coupon is invalid.', 422, ApiErrorCode::ValidationFailed->value);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new ApiException('Coupon is not active yet.', 422, ApiErrorCode::ValidationFailed->value);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new ApiException('Coupon has expired.', 422, ApiErrorCode::ValidationFailed->value);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new ApiException('Coupon usage limit has been reached.', 422, ApiErrorCode::ValidationFailed->value);
        }

        return $coupon;
    }

    private function discountFor(PaymentCoupon $coupon, float $amount): float
    {
        if ($amount < (float) $coupon->minimum_amount) {
            throw new ApiException('Coupon minimum amount is not met.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $discount = $coupon->discount_type === 'fixed'
            ? (float) $coupon->discount_value
            : $amount * ((float) $coupon->discount_value / 100);

        return round(min($amount, $discount), 2);
    }

    private function checkoutInstructions(PaymentGateway $gateway, PackagePayment $payment): array
    {
        return match ($gateway) {
            PaymentGateway::Stripe => [
                'mode' => 'stripe_checkout',
                'gateway_reference' => $payment->gateway_reference,
                'publishable_key' => $this->setting('STRIPE_KEY'),
                'amount' => $payment->payable_amount,
                'currency' => $payment->currency,
                'webhook_endpoint' => url('/api/v1/payments/webhooks/stripe'),
            ],
            PaymentGateway::EasyPaisa => [
                'mode' => 'manual_gateway_confirmation',
                'gateway_reference' => $payment->gateway_reference,
                'amount' => $payment->payable_amount,
                'currency' => $payment->currency,
                'store_id' => $this->setting('EASYPAISA_STORE_ID'),
                'account_msisdn' => $this->setting('EASYPAISA_ACCOUNT_MSISDN'),
                'instructions' => $this->setting('EASYPAISA_PAYMENT_INSTRUCTIONS'),
                'sandbox' => get_setting('easypaisa_sandbox') == 1,
                'webhook_endpoint' => url('/api/v1/payments/webhooks/easypaisa'),
            ],
            PaymentGateway::JazzCash => [
                'mode' => 'manual_gateway_confirmation',
                'gateway_reference' => $payment->gateway_reference,
                'amount' => $payment->payable_amount,
                'currency' => $payment->currency,
                'merchant_id' => $this->setting('JAZZCASH_MERCHANT_ID'),
                'account_msisdn' => $this->setting('JAZZCASH_ACCOUNT_MSISDN'),
                'instructions' => $this->setting('JAZZCASH_PAYMENT_INSTRUCTIONS'),
                'sandbox' => get_setting('jazzcash_sandbox') == 1,
                'webhook_endpoint' => url('/api/v1/payments/webhooks/jazzcash'),
                'note' => 'JazzCash checkout requires live merchant credentials/callback verification before marking payments paid.',
            ],
        };
    }

    private function assertGatewayAvailable(PaymentGateway $gateway): void
    {
        if (get_setting($gateway->value . '_payment_activation') != 1) {
            throw new ApiException(ucfirst($gateway->value) . ' payments are disabled by admin.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $requiredSettings = match ($gateway) {
            PaymentGateway::Stripe => ['STRIPE_KEY', 'STRIPE_SECRET'],
            PaymentGateway::EasyPaisa => ['EASYPAISA_STORE_ID', 'EASYPAISA_ACCOUNT_MSISDN'],
            PaymentGateway::JazzCash => ['JAZZCASH_MERCHANT_ID', 'JAZZCASH_ACCOUNT_MSISDN'],
        };

        foreach ($requiredSettings as $setting) {
            if (blank($this->setting($setting))) {
                throw new ApiException($gateway->value . ' is not fully configured in admin payment settings.', 422, ApiErrorCode::ValidationFailed->value);
            }
        }
    }

    private function setting(string $key): ?string
    {
        $value = get_setting($key);

        return filled($value) ? (string) $value : env($key);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Due',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => $status,
        };
    }
}
