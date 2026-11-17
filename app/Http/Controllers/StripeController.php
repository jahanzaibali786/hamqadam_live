<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\PackagePayment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function pay()
    {
        return view('frontend.payment_gateway.stripe');
    }

    public function create_checkout_session(Request $request)
    {
        $type = (string) $request->session()->get('payment_type', '');
        $data = $request->session()->get('payment_data');

        if (! $request->user() || ! is_array($data) ||
            ! in_array($type, ['package_payment', 'wallet_payment', 'custom_coins'], true) ||
            (isset($data['user_id']) &&
                (int) $data['user_id'] !== (int) $request->user()->id)) {
            return response()->json(['message' => 'Payment session expired. Please restart checkout.'], 422);
        }

        $data['user_id'] = (int) $request->user()->id;
        $amount = round((float) ($data['amount'] ?? -1) * 100);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid checkout amount.'], 422);
        }

        $currency = strtolower((string) Currency::findOrFail(get_setting('system_default_currency'))->code);
        $attempt = (string) ($data['payment_attempt_id'] ?? '');
        if (! $attempt) {
            $attempt = (string) \Illuminate\Support\Str::uuid();
            $data['payment_attempt_id'] = $attempt;
        }

        // The same browser session must not create a second charged Checkout
        // Session when the checkout button is clicked twice.
        Stripe::setApiKey(config('services.stripe.secret') ?: env('STRIPE_SECRET'));
        $existingId = (string) ($data['stripe_checkout_session_id'] ?? '');
        if ($existingId !== '') {
            $existing = CheckoutSession::retrieve($existingId);
            if ((string) $existing->status === 'open' &&
                (string) $existing->client_reference_id === $attempt &&
                (int) $existing->amount_total === (int) $amount &&
                strtolower((string) $existing->currency) === $currency) {
                return response()->json(['id' => $existingId, 'status' => 200]);
            }
            // Never switch to a second Checkout after a paid session.
            if ((string) $existing->payment_status === 'paid') {
                return response()->json(['message' => 'Payment already received. Please check your purchase history.'], 409);
            }
        }

        $session = CheckoutSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => 'Payment'],
                    'unit_amount' => (int) $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'client_reference_id' => $attempt,
            'metadata' => [
                'user_id' => (string) $request->user()->id,
                'payment_type' => $type,
                'package_id' => (string) ($data['package_id'] ?? 0),
            ],
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel'),
        ]);

        $data['stripe_checkout_session_id'] = $session->id;
        $data['currency'] = strtoupper($currency);
        $request->session()->put('payment_data', $data);

        return response()->json(['id' => $session->id, 'status' => 200]);
    }

    public function success(Request $request)
    {
        $type = (string) $request->session()->get('payment_type', '');
        $data = $request->session()->get('payment_data');
        $sessionId = trim((string) $request->query('session_id', ''));

        if (! $request->user() || ! preg_match('/^cs_(test|live)_[A-Za-z0-9_]+$/', $sessionId)) {
            return redirect()->route('package_purchase_history')
                ->with('error', 'We could not identify this checkout. Please contact support before retrying.');
        }

        // Already-recorded purchases may be revisited safely after the
        // browser session was cleared. No duplicate credits are issued.
        $existingPayment = PackagePayment::query()
            ->where('payment_method', 'stripe')
            ->where('gateway_reference', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();
        if ($existingPayment) {
            return redirect()->route('package_payment.invoice', $existingPayment->id);
        }

        if (! is_array($data) || ! in_array($type, ['package_payment', 'wallet_payment', 'custom_coins'], true) ||
            (int) ($data['user_id'] ?? 0) !== (int) $request->user()->id ||
            ! hash_equals((string) ($data['stripe_checkout_session_id'] ?? ''), $sessionId)) {
            return redirect()->route('package_purchase_history')->with('error',
                'We could not restore the payment session. Contact support with your Stripe receipt; do not pay again.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret') ?: env('STRIPE_SECRET'));
            $stripeSession = CheckoutSession::retrieve($sessionId);
            $actualCurrency = strtolower((string) $stripeSession->currency);
            $expectedCurrency = strtolower((string) ($data['currency'] ?? ''));
            $expectedCents = (int) round((float) $data['amount'] * 100);

            if ((string) $stripeSession->status !== 'complete' ||
                (string) $stripeSession->payment_status !== 'paid' ||
                (int) $stripeSession->amount_total !== $expectedCents ||
                $actualCurrency !== $expectedCurrency ||
                (string) $stripeSession->client_reference_id !== (string) ($data['payment_attempt_id'] ?? '') ||
                (string) ($stripeSession->metadata->user_id ?? '') !== (string) $request->user()->id ||
                (string) ($stripeSession->metadata->payment_type ?? '') !== $type ||
                (string) ($stripeSession->metadata->package_id ?? '') !== (string) ($data['package_id'] ?? 0)) {
                throw new \RuntimeException('Stripe checkout verification mismatch.');
            }

            $data['stripe_checkout_session_id'] = $sessionId;
            $paymentDetails = json_encode([
                'status' => 'Success',
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => (string) ($stripeSession->payment_intent ?? ''),
            ]);

            if ($type === 'package_payment') {
                return app(PackagePaymentController::class)->package_payment_done($data, $paymentDetails);
            }

            // Retain the existing wallet / custom-coin checkout products while
            // verifying payment and making their Stripe credits repeat-safe.
            return $this->fulfillNonPackageCheckout($type, $data, $paymentDetails, $request);
        } catch (\Throwable $e) {
            Log::error('Stripe package fulfillment verification failed', [
                'user_id' => $request->user()->id,
                'stripe_checkout_session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('package_purchase_history')->with('error',
                'Your payment could not be verified or activated yet. Contact support with the Stripe receipt; do not pay again.');
        }
    }

    private function fulfillNonPackageCheckout(string $type, array $data, string $details, Request $request)
    {
        $sessionId = (string) ($data['stripe_checkout_session_id'] ?? '');
        $userId = (int) $request->user()->id;

        // Lock the buyer while checking/recording this gateway transaction.
        return DB::transaction(function () use ($type, $data, $details, $sessionId, $userId) {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            if ($type === 'wallet_payment') {
                $existing = Wallet::query()->where('user_id', $userId)
                    ->where('payment_method', 'stripe')
                    ->where('payment_details', 'like', '%' . $sessionId . '%')->first();

                if ($existing) {
                    return redirect()->route('wallet.index')
                        ->with('success', 'This wallet payment was already credited.');
                }

                $wallet = new Wallet();
                $wallet->user_id = $userId;
                $wallet->amount = $data['amount'];
                $wallet->payment_method = 'stripe';
                $wallet->payment_details = $details;
                $wallet->save();

                $user->balance = (float) $user->balance + (float) $data['amount'];
                $user->save();
                session()->forget(['payment_type', 'payment_data']);
                return redirect()->route('wallet.index')->with('success', 'Payment completed.');
            }

            $payment = PackagePayment::query()->where('user_id', $userId)
                ->where('payment_method', 'stripe')
                ->where('gateway_reference', $sessionId)->first();

            if (! $payment) {
                $payment = new PackagePayment();
                $payment->payment_code = now()->format('ymd-His') . '-' . random_int(1000, 9999);
                $payment->user_id = $userId;
                $payment->package_id = 0;
                $payment->payment_method = 'stripe';
                $payment->payment_status = 'Paid';
                $payment->amount = $data['amount'];
                $payment->payable_amount = $data['amount'];
                $payment->currency = strtoupper((string) $data['currency']);
                $payment->payment_details = $details;
                $payment->gateway_reference = $sessionId;
                $payment->gateway_status = 'paid';
                $payment->offline_payment = 2;
                $payment->metadata = [
                    \App\Services\Api\V1\Payment\CustomCoinService::TYPE => [
                        'coins' => (int) $data['coins'],
                        'unit_price' => \App\Services\Api\V1\Payment\CustomCoinService::amountFor(1),
                        'amount' => $data['amount'],
                    ],
                ];
                $payment->save();
            }

            \App\Services\Api\V1\Payment\CustomCoinService::deliverIfPaid($payment);
            session()->forget(['payment_type', 'payment_data']);
            return redirect()->route('package_payment.invoice', $payment->id);
        }, 3);
    }

    public function cancel(Request $request)
    {
        $request->session()->forget('payment_data');
        $request->session()->forget('payment_type');
        flash(translate('Payment is cancelled'))->error();
        return redirect()->route('home');
    }
}
