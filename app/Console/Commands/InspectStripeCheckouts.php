<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PackagePayment;
use Illuminate\Console\Command;

/**
 * Phase 6B: read-only gateway reconciliation preview. Never changes payment
 * status, members, coupons, or coin balances.
 */
final class InspectStripeCheckouts extends Command
{
    protected $signature = 'payments:inspect-stripe {--limit=30 : Maximum Stripe payments to inspect, between 1 and 100}';
    protected $description = 'Compare recent pending Stripe purchases to provider sessions without settling them';

    public function handle(): int
    {
        $secret = trim((string) (get_setting('STRIPE_SECRET') ?: config('services.stripe.secret', '')));
        if ($secret === '') {
            $this->error('Stripe secret key is not configured; inspection aborted.');
            return self::FAILURE;
        }
        if (! class_exists(\Stripe\Checkout\Session::class)) {
            $this->error('Stripe SDK is unavailable.');
            return self::FAILURE;
        }
        $limit = max(1, min(100, (int) $this->option('limit')));
        $payments = PackagePayment::query()->where('payment_method', 'stripe')
            ->whereIn('payment_status', ['Due', 'Failed', 'Cancelled'])
            ->latest()->limit($limit)->get();
        \Stripe\Stripe::setApiKey($secret);
        $rows = [];
        $mismatches = 0;
        foreach ($payments as $payment) {
            $sessionId = (array) ($payment->metadata ?? []);
            $sessionId = $sessionId['stripe_session_id'] ?? null;
            if (! $sessionId || ! str_starts_with((string) $sessionId, 'cs_')) {
                $rows[] = [$payment->id, $payment->payment_status, 'missing_session', '-'];
                continue;
            }
            try {
                $session = \Stripe\Checkout\Session::retrieve((string) $sessionId);
                $amount = (int) round(((float) ($payment->payable_amount ?? $payment->amount)) * 100);
                $same = (int) ($session->amount_total ?? -1) === $amount
                    && strtolower((string) ($session->currency ?? '')) === strtolower((string) $payment->currency)
                    && (string) ($session->id ?? '') === (string) $sessionId;
                if (! $same) $mismatches++;
                $rows[] = [$payment->id, $payment->payment_status, (string) ($session->payment_status ?? 'unknown'), $same ? 'matches' : 'MISMATCH'];
            } catch (\Throwable $exception) {
                report($exception);
                $rows[] = [$payment->id, $payment->payment_status, 'provider_error', 'review'];
            }
        }
        $this->table(['Payment ID', 'Local state', 'Stripe session', 'Amount/currency'], $rows);
        $this->line("Inspected: {$payments->count()}. Mismatches: {$mismatches}. No records were changed.");
        return $mismatches > 0 ? self::FAILURE : self::SUCCESS;
    }
}
