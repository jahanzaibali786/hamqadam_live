<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Payment;

use App\Models\PaymentRefundAttempt;
use App\Models\PaymentRefundRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Administrative full-refund flow with durable retry identity.
 * Never calls Stripe without explicit merchant configuration and approval.
 */
final class StripeRefundExecutionService
{
    public function execute(PaymentRefundRequest $refund, int $reviewerId): PaymentRefundAttempt
    {
        if (! config('hamqadam_payments.stripe_refunds_enabled', false)) {
            throw ValidationException::withMessages(['refund' => 'Stripe refund execution is disabled. Enable it after sandbox testing.']);
        }

        $secret = trim((string) (get_setting('STRIPE_SECRET') ?: config('services.stripe.secret', '')));
        if ($secret === '') {
            throw ValidationException::withMessages(['refund' => 'Stripe credentials are not configured.']);
        }

        $attempt = DB::transaction(function () use ($refund): PaymentRefundAttempt {
            $record = PaymentRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $payment = $record->payment;
            if (! $payment || $payment->payment_method !== 'stripe' || $payment->payment_status !== 'Paid'
                || ! in_array($record->status, ['requested', 'reviewing'], true)) {
                throw ValidationException::withMessages(['refund' => 'Only open, paid Stripe purchases may be refunded.']);
            }

            $amount = (int) round((float) $record->amount * 100);
            if ($amount <= 0 || $amount > (int) round((float) ($payment->payable_amount ?? $payment->amount) * 100)) {
                throw ValidationException::withMessages(['refund' => 'Refund amount is invalid.']);
            }
            if (strtoupper((string) $record->currency) !== strtoupper((string) $payment->currency)) {
                throw ValidationException::withMessages(['refund' => 'Refund currency differs from purchase currency.']);
            }
            $meta = (array) ($payment->metadata ?? []);
            if (empty($meta['stripe_session_id'])) {
                throw ValidationException::withMessages(['refund' => 'Stripe checkout session is missing.']);
            }

            return PaymentRefundAttempt::query()->firstOrCreate(
                ['refund_request_id' => $record->id],
                [
                    'package_payment_id' => $payment->id,
                    'provider' => 'stripe',
                    'idempotency_key' => 'hamqadam_refund_v1_' . $record->id,
                    'amount_minor' => $amount,
                    'currency' => strtoupper((string) $record->currency),
                    'status' => 'reserved',
                ]
            );
        }, 3);

        if ($attempt->status === 'succeeded') return $attempt;
        // The same idempotency key is reused on every retry, including
        // ambiguous provider timeouts; this prevents duplicate refund creation.
        try {
            \Stripe\Stripe::setApiKey($secret);
            $payment = $attempt->payment;
            $meta = (array) ($payment->metadata ?? []);
            $session = \Stripe\Checkout\Session::retrieve($meta['stripe_session_id']);
            if (($session->payment_status ?? null) !== 'paid' || ! str_starts_with((string) ($session->payment_intent ?? ''), 'pi_')
                || strtolower((string) ($session->currency ?? '')) !== strtolower($attempt->currency)
                || (int) ($session->amount_total ?? 0) < $attempt->amount_minor) {
                throw ValidationException::withMessages(['refund' => 'Stripe original charge does not match the refund attempt.']);
            }
            $result = \Stripe\Refund::create(
                ['payment_intent' => (string) $session->payment_intent, 'amount' => $attempt->amount_minor, 'metadata' => ['hamqadam_refund_request_id' => (string) $refund->id]],
                ['idempotency_key' => $attempt->idempotency_key]
            );
            $status = (string) ($result->status ?? 'pending');
            DB::transaction(function () use ($attempt, $result, $status, $reviewerId): void {
                $locked = PaymentRefundAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
                $locked->update([
                    'status' => $status === 'succeeded' ? 'succeeded' : 'pending',
                    'provider_refund_id' => (string) $result->id,
                    'provider_checked_at' => now(),
                    'last_error' => null,
                ]);
                if ($status === 'succeeded') {
                    PaymentRefundRequest::query()->whereKey($locked->refund_request_id)
                        ->whereIn('status', ['requested', 'reviewing'])->update([
                            'status' => 'provider_refund_confirmed',
                            'provider_refund_reference' => (string) $result->id,
                            'reviewed_by' => $reviewerId,
                            'reviewed_at' => now(),
                        ]);
                }
            }, 3);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $attempt->update(['status' => 'unknown', 'last_error' => 'Provider call unsuccessful or outcome uncertain; retry with same idempotency key.']);
            throw ValidationException::withMessages(['refund' => 'Stripe refund not verified. The attempt can be retried safely.']);
        }

        return $attempt->fresh();
    }
}
