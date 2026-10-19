<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ClientScopeSetting;
use App\Models\PaymentCoupon;
use App\Models\RewardRule;
use App\Models\SponsoredListing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Self-contained, repeatable example settings. Never run against live accounts. */
final class HamqadamQaConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        foreach (['reward_rules', 'sponsored_listings', 'client_scope_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table {$table} is missing; migrate the staging database first.");
            }
        }

        foreach ([
            ['profile_completed', 'QA DEMO — completed profile', 15, 1],
            ['first_interest', 'QA DEMO — first interest', 5, 1],
            ['got_match', 'QA DEMO — mutual Got Match', 30, 3],
        ] as [$key, $title, $coins, $limit]) {
            // Do not overwrite a rule somebody has already configured in staging.
            RewardRule::firstOrCreate(['event_key' => $key], [
                'title' => $title, 'coin_reward' => $coins, 'max_per_user' => $limit,
                'active' => true, 'conditions' => ['qa_reference' => true],
            ]);
        }
        ClientScopeSetting::firstOrCreate(['key' => 'show_ads_to_paid'], ['value' => '0']);

        foreach ([
            ['QA DEMO — Family Guidance', 'home', 25],
            ['QA DEMO — Discovery Highlight', 'discovery', 35],
            ['QA DEMO — Match Advice', 'match', 20],
        ] as [$title, $placement, $priority]) {
            SponsoredListing::firstOrCreate(['title' => $title], [
                'sponsor_name' => 'QA DEMO — Not a real advertiser',
                'target_url' => 'https://example.com/',
                'placement' => $placement, 'priority' => $priority, 'active' => true,
                'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(30),
                'eligible_plan_ids' => [], 'impressions' => 0, 'clicks' => 0,
            ]);
        }

        if (Schema::hasTable('payment_coupons')) {
            $data = [
                'discount_type' => 'percentage', 'discount_value' => 10,
                'minimum_amount' => 200, 'usage_limit' => 100,
                'used_count' => 0, 'starts_at' => now()->subDay(),
                'expires_at' => now()->addDays(60), 'active' => true,
            ];
            if (Schema::hasColumn('payment_coupons', 'per_user_limit')) $data['per_user_limit'] = 1;
            PaymentCoupon::firstOrCreate(['code' => 'QADEMO10'], $data);
        }

        // Approximate public city centers, not member GPS locations. Leave existing entries unchanged.
        if (Schema::hasTable('city_geo_coordinates') && Schema::hasTable('cities') && Schema::hasColumn('cities', 'country_id')) {
            foreach ([
                'Lahore' => [31.5204, 74.3587],
                'Islamabad' => [33.6844, 73.0479],
                'Karachi' => [24.8607, 67.0011],
            ] as $name => [$latitude, $longitude]) {
                $id = DB::table('cities')->where('name', $name)->where('country_id', 166)->value('id');
                if ($id !== null && ! DB::table('city_geo_coordinates')->where('city_id', (int) $id)->exists()) {
                    DB::table('city_geo_coordinates')->insert([
                        'city_id' => (int) $id, 'latitude' => $latitude, 'longitude' => $longitude,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}

