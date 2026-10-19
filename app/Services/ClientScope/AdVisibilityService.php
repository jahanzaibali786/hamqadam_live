<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\ClientScopeSetting;
use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Support\Collection;

/** Shared web/mobile sponsored-content entitlement logic. */
class AdVisibilityService
{
    public function visibleFor(User $user, ?string $placement = null, int $limit = 12): Collection
    {
        $package = $user->member?->package;
        $isPaid = (bool) ($package && (float) $package->price > 0
            && $user->member?->package_validity
            && \Carbon\Carbon::parse($user->member->package_validity)->endOfDay()->isFuture());
        $adFree = app(PremiumEntitlementService::class)->has($user, 'ad_free');

        if ($adFree && $isPaid) {
            return collect();
        }
        if ($isPaid && ClientScopeSetting::valueOf('show_ads_to_paid', '0') !== '1') {
            return collect();
        }

        $packageId = $user->member?->current_package_id;
        // Restrict listing queries to live placements; enforce eligibility after
        // decoding the JSON list, without trusting client-supplied plan IDs.
        $query = SponsoredListing::query()->live()->orderByDesc('priority')->orderByDesc('id');
        if ($placement !== null) {
            $query->where('placement', $placement);
        }
        return $query->limit(150)->get()->filter(static function (SponsoredListing $item) use ($packageId): bool {
            $ids = array_map('intval', (array) ($item->eligible_plan_ids ?? []));
            return $ids === [] || ($packageId !== null && in_array((int) $packageId, $ids, true));
        })->take(max(1, min(30, $limit)))->values();
    }
}
