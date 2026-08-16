<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class RegistrationReward
{
    public static function basicPackage(): ?Package
    {
        return Package::where('active', 1)
            ->where(function ($query) {
                $query->where('name', 'Basic Free')
                    ->orWhere('plan_tier', 'free');
            })
            ->orderByRaw("CASE WHEN name = 'Basic Free' THEN 0 ELSE 1 END")
            ->orderBy('price')
            ->orderBy('id')
            ->first()
            ?: Package::where('price', 0)->where('active', 1)->orderBy('id')->first()
            ?: Package::where('id', 1)->where('active', 1)->first();
    }
    public static function nextRecommendedPackage(?Package $currentPackage = null): ?Package
    {
        $currentPackage ??= self::basicPackage();

        return Package::where('active', 1)
            ->when($currentPackage, fn ($query) => $query->where('id', '!=', $currentPackage->id))
            ->when($currentPackage, fn ($query) => $query->where('price', '>=', (float) $currentPackage->price))
            ->orderBy('price')
            ->orderBy('id')
            ->first();
    }

    public static function rewardCoins(): int
    {
        return (int) (self::basicPackage()?->express_interest ?? 0);
    }

    public static function applyBasicPackage(User $user, string $paymentMethod = 'registration_reward'): ?PackagePayment
    {
        $package = self::basicPackage();
        $member = $user->member ?: Member::where('user_id', $user->id)->first();

        if (! $package || ! $member) {
            return null;
        }

        $subscriptionEndsAt = now()->addDays((int) $package->validity);
        $payment = self::ensurePackagePayment($user, $package, $paymentMethod, $subscriptionEndsAt);

        if ((int) $member->current_package_id !== (int) $package->id || (int) $user->has_purchased_free_package !== 1) {
            $member->current_package_id = $package->id;
            $member->remaining_interest += (int) $package->express_interest;
            $member->remaining_photo_gallery += (int) $package->photo_gallery;
            $member->remaining_contact_view += (int) $package->contact;
            $member->remaining_profile_viewer_view += (int) $package->profile_viewers_view;
            $member->remaining_profile_image_view += (int) $package->profile_image_view;
            $member->remaining_gallery_image_view += (int) $package->gallery_image_view;
            $member->auto_profile_match = $package->auto_profile_match;
            $member->auto_horoscope_profile_match = $package->auto_horoscope_profile_match;
            $member->package_validity = $subscriptionEndsAt->toDateString();
            $member->save();
        }

        $user->membership = 1;
        $user->approved = 1;
        $user->email_verified_at ??= now();
        $user->has_purchased_free_package = 1;
        $user->save();

        return $payment;
    }
    public static function verifyRegisteredUser(User $user): void
    {
        $user->forceFill([
            'approved' => 1,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();
    }

    private static function ensurePackagePayment(User $user, Package $package, string $paymentMethod, $subscriptionEndsAt): PackagePayment
    {
        $existing = PackagePayment::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->whereIn('payment_method', [$paymentMethod, 'registration_reward', 'free_package_purchased'])
            ->where('payment_status', 'Paid')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $now = now();
        $attributes = [
            'payment_code' => 'REG-' . $now->format('ymd-His') . '-' . random_int(1000, 9999),
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_method' => $paymentMethod,
            'payment_status' => 'Paid',
            'payment_details' => json_encode([
                'source' => 'registration_auto_activation',
                'note' => 'Basic package activated automatically after registration.',
            ]),
            'amount' => 0,
            'offline_payment' => 0,
        ];

        if (Schema::hasColumn('package_payments', 'discount_amount')) {
            $attributes['discount_amount'] = (float) $package->price;
        }

        if (Schema::hasColumn('package_payments', 'payable_amount')) {
            $attributes['payable_amount'] = 0;
        }

        if (Schema::hasColumn('package_payments', 'currency')) {
            $attributes['currency'] = 'PKR';
        }

        if (Schema::hasColumn('package_payments', 'gateway_status')) {
            $attributes['gateway_status'] = 'paid';
        }

        if (Schema::hasColumn('package_payments', 'paid_at')) {
            $attributes['paid_at'] = $now;
        }

        if (Schema::hasColumn('package_payments', 'subscription_ends_at')) {
            $attributes['subscription_ends_at'] = $subscriptionEndsAt;
        }

        if (Schema::hasColumn('package_payments', 'invoice_number')) {
            $attributes['invoice_number'] = 'INV-REG-' . $now->format('YmdHis') . '-' . $user->id;
        }

        if (Schema::hasColumn('package_payments', 'metadata')) {
            $attributes['metadata'] = [
                'activation_type' => 'registration_reward',
                'package_price_waived' => (float) $package->price,
                'reward_coins' => (int) $package->express_interest,
            ];
        }

        return PackagePayment::create($attributes);
    }
}
