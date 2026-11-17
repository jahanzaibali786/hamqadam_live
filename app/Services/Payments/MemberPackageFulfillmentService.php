<?php

namespace App\Services\Payments;

use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\PaymentCoupon;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Activate one package purchase at most once for a verified payment attempt.
 *
 * Never infer the owner from members.id: the only allowed relationship is
 * members.user_id = users.id (with the default soft-delete scope).
 */
final class MemberPackageFulfillmentService
{
    /**
     * @return array{payment:PackagePayment, activated:bool, newly_activated:bool}
     */
    public function fulfill(User $buyer, array $paymentData, ?string $paymentDetails): array
    {
        $packageId = (int) ($paymentData['package_id'] ?? 0);
        $method = (string) ($paymentData['payment_method'] ?? '');
        $userId = (int) $buyer->getKey();
        $ownerId = (int) ($paymentData['user_id'] ?? $userId);

        if ($ownerId !== $userId || $userId <= 0 || $packageId <= 0 || $method === '') {
            throw new \InvalidArgumentException('Payment data does not match the current account.');
        }

        $stripeReference = trim((string) ($paymentData['stripe_checkout_session_id'] ?? ''));
        if ($method === 'stripe' && ! preg_match('/^cs_(test|live)_[A-Za-z0-9_]+$/', $stripeReference)) {
            throw new \InvalidArgumentException('Stripe payment is missing a verified Checkout reference.');
        }
        $attempt = trim((string) ($paymentData['payment_attempt_id'] ?? ''));
        $reference = $stripeReference !== ''
            ? $stripeReference
            : ($attempt !== '' ? 'package-attempt:' . $attempt : null);

        return DB::transaction(function () use ($buyer, $paymentData, $paymentDetails, $packageId, $method, $userId, $reference) {
            // Serialize purchases for the same user even without a unique constraint
            // on package_payments.gateway_reference in legacy databases.
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            $existing = $reference
                ? PackagePayment::query()
                    ->where('payment_method', $method)
                    ->where('gateway_reference', $reference)
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($existing && ((int) $existing->user_id !== $userId ||
                    (int) $existing->package_id !== $packageId)) {
                throw new \RuntimeException('The gateway payment reference is already associated with another purchase.');
            }

            $payment = $existing ?? new PackagePayment();
            if (! $existing) {
                $payment->payment_code = now()->format('ymd-His') . '-' . random_int(1000, 9999);
                $payment->user_id = $userId;
                $payment->package_id = $packageId;
                $payment->payment_method = $method;
                $payment->payment_status = 'Paid';
                $payment->amount = $paymentData['package_amount'] ?? $paymentData['amount'];
                $payment->discount_amount = $paymentData['discount_amount'] ?? 0;
                $payment->payable_amount = $paymentData['payable_amount'] ?? $paymentData['amount'];
                $payment->coupon_id = $paymentData['coupon_id'] ?? null;
                $payment->payment_details = $paymentDetails;
                $payment->offline_payment = 2;
                $payment->gateway_reference = $reference;
                $payment->gateway_status = 'paid_pending_fulfillment';
                $payment->paid_at = now();
                if (! empty($paymentData['currency'])) {
                    $payment->currency = strtoupper((string) $paymentData['currency']);
                }
            }

            $metadata = (array) ($payment->metadata ?? []);
            if (($metadata['activation_status'] ?? null) === 'activated') {
                return ['payment' => $payment, 'activated' => true, 'newly_activated' => false];
            }

            $members = Member::query()->where('user_id', $userId)
                ->lockForUpdate()->limit(2)->get();
            $package = Package::query()->find($packageId);

            // Stripe may already have charged the card. Save that fact, but
            // NEVER grant a plan to a guessed / deleted / duplicate member.
            if ($members->count() !== 1 || ! $package) {
                $reason = ! $package ? 'package_missing' :
                    ($members->isEmpty() ? 'member_missing' : 'member_ambiguous');

                $metadata['activation_status'] = 'pending_review';
                $metadata['activation_issue'] = $reason;
                $metadata['payment_attempt_id'] = $paymentData['payment_attempt_id'] ?? null;
                $payment->metadata = $metadata;
                $payment->payment_status = 'Paid';
                $payment->gateway_status = 'paid_pending_fulfillment';
                $payment->save();

                Log::warning('Paid package needs manual fulfillment review', [
                    'user_id' => $userId,
                    'payment_id' => $payment->id,
                    'package_id' => $packageId,
                    'reason' => $reason,
                ]);

                return ['payment' => $payment, 'activated' => false, 'newly_activated' => false];
            }

            $member = $members->first();

            $member->current_package_id = $package->id;
            foreach ([
                'remaining_interest' => 'express_interest',
                'remaining_photo_gallery' => 'photo_gallery',
                'remaining_contact_view' => 'contact',
                'remaining_profile_viewer_view' => 'profile_viewers_view',
                'remaining_profile_image_view' => 'profile_image_view',
                'remaining_gallery_image_view' => 'gallery_image_view',
            ] as $memberColumn => $packageColumn) {
                $member->{$memberColumn} = (int) ($member->{$memberColumn} ?? 0)
                    + (int) ($package->{$packageColumn} ?? 0);
            }
            $member->auto_profile_match = $package->auto_profile_match;
            $member->auto_horoscope_profile_match = $package->auto_horoscope_profile_match;
            $endDate = Carbon::now()->addDays((int) $package->validity);
            $member->package_validity = $endDate->toDateString();
            $member->save();

            $user->membership = (int) $package->id === 1 ? 1 : 2;
            $user->save();

            $payment->payment_status = 'Paid';
            $payment->gateway_status = 'paid';
            $payment->subscription_ends_at = $endDate;
            $metadata['activation_status'] = 'activated';
            $metadata['activated_member_id'] = $member->id;
            $metadata['activation_issue'] = null;
            $metadata['payment_attempt_id'] = $paymentData['payment_attempt_id'] ?? null;
            $payment->metadata = $metadata;
            $payment->save();

            if ($payment->coupon_id) {
                PaymentCoupon::query()->whereKey($payment->coupon_id)->increment('used_count');
            }

            // Existing one-off referral commission, now guarded against
            // missing referrers and executed once in this locked transaction.
            if (addon_activation('referral_system') &&
                $user->referred_by && (int) $user->referral_comission === 0) {
                $referrer = User::query()->whereKey($user->referred_by)
                    ->lockForUpdate()->first();
                if ($referrer && $referrer->id !== $user->id) {
                    $commission = (float) get_setting('referred_by_user_commission');
                    if ($commission > 0) {
                        $wallet = new Wallet();
                        $wallet->user_id = $referrer->id;
                        $wallet->amount = $commission;
                        $wallet->payment_method = 'reffered_commission';
                        $wallet->payment_details = '';
                        $wallet->referral_user = $user->id;
                        $wallet->save();
                        $referrer->balance = (float) $referrer->balance + $commission;
                        $referrer->save();
                    }
                    $user->referral_comission = 1;
                    $user->save();
                }
            }

            return ['payment' => $payment, 'activated' => true, 'newly_activated' => true];
        }, 3);
    }
}
