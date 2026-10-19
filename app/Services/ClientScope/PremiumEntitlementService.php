<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\User;
use Carbon\Carbon;

/** Single policy for the web and mobile experience. Defaults to deny for paid-only flags. */
final class PremiumEntitlementService
{
    public const PAID_FEATURES = [
        'profile_boost', 'priority_search', 'unlimited_messaging',
        'advanced_filters', 'priority_interest', 'ad_free',
    ];

    public function has(User $user, string $feature): bool
    {
        if (! in_array($feature, self::PAID_FEATURES, true)) return false;
        $member = $user->member;
        $package = $member?->package;
        // A paid flag must never survive an expired subscription or an inactive package.
        if (! $member || ! $package || (float) ($package->price ?? 0) <= 0) return false;
        if (! $member->package_validity || Carbon::parse($member->package_validity)->endOfDay()->isPast()) return false;
        if (isset($package->active) && ! $package->active) return false;
        $flags = (array) ($package->feature_flags ?? []);
        if (array_is_list($flags)) return in_array($feature, $flags, true);
        return filter_var($flags[$feature] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function payload(User $user): array
    {
        $result = [];
        foreach (self::PAID_FEATURES as $key) {
            $result[$key] = $this->has($user, $key);
        }
        return [
            'current_package_id' => $user->member?->current_package_id,
            'package_validity' => $user->member?->package_validity,
            'premium_features' => $result,
            'policy' => 'Paid plan with active validity and explicit enabled plan feature flag.',
        ];
    }
}
