<?php

namespace App\Services\Api\V1\Payment;

use App\Models\Member;
use App\Models\PackagePayment;
use App\Utility\EmailUtility;
use Illuminate\Support\Facades\DB;

/**
 * Custom coins: the member types how many coins they want (integer), we price
 * them with the admin-configured per-coin charge, and the purchase rides the
 * SAME package_payments checkout pipeline that package purchases use — same
 * gateways, webhooks, invoices and history.
 *
 * Security model:
 * - The payable amount is ALWAYS computed server-side (coins × unit price);
 *   any client-sent amount is ignored.
 * - The coin count + unit price + amount snapshot are stored in the payment's
 *   metadata at creation and re-verified against the stored amount before any
 *   coin credit — so a tampered/edited record can never credit coins.
 */
class CustomCoinService
{
    public const TYPE = 'custom_coins';

    /**
     * Canonical amount for a coin count. Single source of truth for pricing —
     * used to quote the form and to re-check the amount at approval time.
     */
    public static function amountFor(int $coins): float
    {
        return round($coins * custom_coin_unit_price(), 2);
    }

    /**
     * Create a pending custom-coin purchase (payment row) without touching the
     * member's package. Returns the created payment.
     *
     * @param  array{gateway: string, gateway_reference: string, currency: string}  $base
     */
    public static function createPurchase(Member $member, int $coins, array $base): PackagePayment
    {
        if ($coins < 1 || $coins > 1000000) {
            abort(422, 'Please enter a valid number of coins.');
        }

        $unitPrice = custom_coin_unit_price();
        $amount = self::amountFor($coins);

        return PackagePayment::create([
            'payment_code' => now()->format('ymd-His').'-'.random_int(1000, 9999),
            'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.random_int(1000, 9999),
            'user_id' => $member->user_id,
            'package_id' => 0, // not a package subscription
            'payment_method' => $base['gateway'],
            'payment_status' => 'Due',
            'amount' => $amount,
            'discount_amount' => 0,
            'payable_amount' => $amount,
            'currency' => $base['currency'],
            'gateway_reference' => $base['gateway_reference'],
            'gateway_status' => 'pending',
            'offline_payment' => 2,
            'metadata' => [
                self::TYPE => [
                    'coins' => $coins,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ],
            ],
        ]);
    }

    /**
     * The coin payload stored on a purchase (null when it is a package payment).
     *
     * @return array{coins: int, unit_price: float, amount: float}|null
     */
    public static function payloadOf(?PackagePayment $payment): ?array
    {
        $payload = $payment?->metadata[self::TYPE] ?? null;

        if (! is_array($payload) || empty($payload['coins'])) {
            return null;
        }

        return [
            'coins' => (int) $payload['coins'],
            'unit_price' => (float) ($payload['unit_price'] ?? 0),
            'amount' => (float) ($payload['amount'] ?? 0),
        ];
    }

    /**
     * Deliver the coins for a PAID custom-coin purchase and send the same
     * invoice email package purchases send. Idempotent — a payment can only be
     * delivered once (guard + payment_status check).
     */
    public static function deliverIfPaid(PackagePayment $payment): bool
    {
        $payload = self::payloadOf($payment);

        if ($payload === null) {
            return false; // plain package payment — not ours
        }

        if ($payment->payment_status !== 'Paid') {
            return false; // not paid yet
        }

        if ((int) ($payment->metadata['coins_delivered'] ?? 0) === 1) {
            return true; // already delivered
        }

        // Server-side total check: stored amount must equal coins × CURRENT
        // unit price only if the snapshot matches; use the snapshot price so
        // a later admin price change cannot change already-paid invoices.
        $expectedAmount = round($payload['coins'] * $payload['unit_price'], 2);

        if (round((float) $payment->amount, 2) !== $expectedAmount
            || round((float) $payment->payable_amount, 2) !== $expectedAmount) {
            return false; // tampered / inconsistent record — refuse to credit
        }

        DB::transaction(function () use ($payment, $payload) {
            $member = Member::where('user_id', $payment->user_id)->lockForUpdate()->first();

            if (! $member) {
                throw new \RuntimeException('Member not found for coin purchase.');
            }

            credit_coins_to_wallet($member, $payload['coins']);

            $payment->forceFill([
                'metadata' => array_merge((array) ($payment->metadata ?? []), ['coins_delivered' => 1]),
            ])->save();

            EmailUtility::custom_coin_purchase_email($member->user, $payment, $payload['coins']);
        });

        return true;
    }
}
