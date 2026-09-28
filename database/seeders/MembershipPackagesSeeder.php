<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * HamQadam membership packages (product decision):
 *
 *   Free        — Rs 0   : the starter tier every member lands on
 *   Silver      — Rs 500 : light boost, one month
 *   Gold        — Rs 1000: the popular middle tier, three months
 *   Platinum    — Rs 2000: everything unlocked, six months
 *
 * Coins live in `express_interest` (the SAME wallet remaining_interest is
 * seeded from on purchase/activation) and the feature flags carry what each
 * tier unlocks. Prices are PKR. Idempotent on `plan_tier`, so re-running the
 * seeder updates rather than duplicates.
 */
class MembershipPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'plan_tier' => 'free',
                'name' => 'Free',
                'price' => 0,
                'validity' => 3650, // effectively no expiry
                'express_interest' => 25,
                'photo_gallery' => 5,
                'contact' => 0,
                'profile_viewers_view' => 10,
                'profile_image_view' => 1,
                'gallery_image_view' => 0,
                'feature_flags' => ['ai_matching' => true, 'advanced_search' => false, 'chat_coins' => true],
            ],
            [
                'plan_tier' => 'silver',
                'name' => 'Silver',
                'price' => 500,
                'validity' => 30,
                'express_interest' => 100,
                'photo_gallery' => 15,
                'contact' => 0,
                'profile_viewers_view' => 50,
                'profile_image_view' => 1,
                'gallery_image_view' => 1,
                'feature_flags' => ['ai_matching' => true, 'advanced_search' => true, 'chat_coins' => true, 'see_visitors' => true],
            ],
            [
                'plan_tier' => 'gold',
                'name' => 'Gold',
                'price' => 1000,
                'validity' => 90,
                'express_interest' => 300,
                'photo_gallery' => 25,
                'contact' => 1,
                'profile_viewers_view' => 150,
                'profile_image_view' => 1,
                'gallery_image_view' => 1,
                'feature_flags' => ['ai_matching' => true, 'advanced_search' => true, 'chat_coins' => true, 'see_visitors' => true, 'contact_visible' => true, 'profile_boost' => true],
            ],
            [
                'plan_tier' => 'platinum',
                'name' => 'Platinum',
                'price' => 2000,
                'validity' => 180,
                'express_interest' => 750,
                'photo_gallery' => 50,
                'contact' => 1,
                'profile_viewers_view' => 500,
                'profile_image_view' => 1,
                'gallery_image_view' => 1,
                'feature_flags' => ['ai_matching' => true, 'advanced_search' => true, 'chat_coins' => true, 'see_visitors' => true, 'contact_visible' => true, 'profile_boost' => true, 'priority_support' => true, 'horoscope_full' => true],
            ],
        ];

        // Legacy untiered rows ("Default", old duplicates) must not appear in
        // the app's plans list next to the four real tiers.
        Package::whereNull('plan_tier')->orWhere('plan_tier', '')->update(['active' => 0]);

        foreach ($tiers as $tier) {
            Package::updateOrCreate(
                ['plan_tier' => $tier['plan_tier']],
                [
                    'name' => $tier['name'],
                    'price' => $tier['price'],
                    'validity' => $tier['validity'],
                    'express_interest' => $tier['express_interest'],
                    'photo_gallery' => $tier['photo_gallery'],
                    'contact' => $tier['contact'],
                    'profile_viewers_view' => $tier['profile_viewers_view'],
                    'profile_image_view' => $tier['profile_image_view'],
                    'gallery_image_view' => $tier['gallery_image_view'],
                    'auto_profile_match' => 1,
                    'auto_horoscope_profile_match' => 1,
                    'feature_flags' => $tier['feature_flags'],
                    'active' => 1,
                    'activate_on_registration' => $tier['plan_tier'] === 'free',
                    'is_recurring' => false,
                ],
            );
        }
    }
}
