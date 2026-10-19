<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Database\Seeders\HamqadamQaConfigurationSeeder;
use Database\Seeders\HamqadamQaGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Isolated in-memory SQLite test: never reads or alters the project database. */
final class QaSeederConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('reward_rules', function (Blueprint $t) {
            $t->id(); $t->string('event_key')->unique(); $t->string('title');
            $t->unsignedInteger('coin_reward')->default(0);
            $t->unsignedInteger('max_per_user')->default(1);
            $t->boolean('active'); $t->json('conditions')->nullable(); $t->timestamps();
        });
        Schema::create('sponsored_listings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('title'); $t->string('sponsor_name')->nullable();
            $t->string('image')->nullable(); $t->string('target_url')->nullable();
            $t->string('placement'); $t->unsignedInteger('priority')->default(0);
            $t->boolean('active'); $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable(); $t->json('eligible_plan_ids')->nullable();
            $t->unsignedBigInteger('impressions')->default(0);
            $t->unsignedBigInteger('clicks')->default(0); $t->timestamps();
        });
        Schema::create('client_scope_settings', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('value')->nullable(); $t->timestamps();
        });
        Schema::create('payment_coupons', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique(); $t->string('discount_type');
            $t->decimal('discount_value',12,2); $t->decimal('minimum_amount',12,2);
            $t->unsignedInteger('usage_limit')->nullable(); $t->unsignedInteger('used_count')->default(0);
            $t->unsignedInteger('per_user_limit')->nullable();
            $t->timestamp('starts_at')->nullable(); $t->timestamp('expires_at')->nullable();
            $t->boolean('active'); $t->timestamps();
        });
    }

    public function test_demo_config_is_idempotent_and_does_not_replace_custom_rewards(): void
    {
        (new HamqadamQaConfigurationSeeder())->run();
        $this->assertSame(3, DB::table('reward_rules')->count());
        $this->assertSame(3, DB::table('sponsored_listings')->count());
        $this->assertSame(1, DB::table('payment_coupons')->count());
        $this->assertSame('0', DB::table('client_scope_settings')->where('key','show_ads_to_paid')->value('value'));

        DB::table('reward_rules')->where('event_key','got_match')->update(['coin_reward' => 77]);
        (new HamqadamQaConfigurationSeeder())->run();
        $this->assertSame(3, DB::table('reward_rules')->count());
        $this->assertSame(3, DB::table('sponsored_listings')->count());
        $this->assertSame(1, DB::table('payment_coupons')->count());
        $this->assertSame(77, (int) DB::table('reward_rules')->where('event_key','got_match')->value('coin_reward'));
    }

    public function test_environment_guard_rejects_production_and_restores_environment(): void
    {
        $original = app()->environment();
        try {
            app()->detectEnvironment(fn () => 'production');
            try {
                HamqadamQaGuard::assertSafe();
                $this->fail('Production must be rejected');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('Never run them in production', $e->getMessage());
            }
        } finally {
            app()->detectEnvironment(fn () => $original);
        }
    }
}

