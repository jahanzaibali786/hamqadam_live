<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Models\Member;
use App\Models\Package;
use App\Models\SponsoredListing;
use App\Models\User;
use App\Services\ClientScope\AdVisibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Exercises real sponsorship date, placement and premium policy with no live users. */
final class QaSponsoredVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'',
            'foreign_key_constraints'=>true,
        ]);
        DB::purge('sqlite'); DB::setDefaultConnection('sqlite');
        Schema::create('sponsored_listings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('title');
            $t->string('sponsor_name')->nullable(); $t->string('image')->nullable();
            $t->string('target_url')->nullable(); $t->string('placement');
            $t->unsignedInteger('priority')->default(0); $t->boolean('active');
            $t->timestamp('starts_at')->nullable(); $t->timestamp('ends_at')->nullable();
            $t->json('eligible_plan_ids')->nullable();
            $t->unsignedBigInteger('impressions')->default(0);
            $t->unsignedBigInteger('clicks')->default(0); $t->timestamps();
        });
        Schema::create('client_scope_settings', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('value'); $t->timestamps();
        });
        SponsoredListing::query()->create(['title'=>'Visible in Discovery','placement'=>'discovery','priority'=>5,
            'active'=>true,'starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(), 'eligible_plan_ids'=>[]]);
        SponsoredListing::query()->create(['title'=>'Expired','placement'=>'discovery','priority'=>10,
            'active'=>true,'ends_at'=>now()->subDay(),'eligible_plan_ids'=>[]]);
        SponsoredListing::query()->create(['title'=>'Disabled','placement'=>'discovery','priority'=>10,
            'active'=>false,'eligible_plan_ids'=>[]]);
        SponsoredListing::query()->create(['title'=>'Match Only','placement'=>'match','priority'=>10,
            'active'=>true,'eligible_plan_ids'=>[]]);
    }

    private function memberOnPlan(int $price, array $flags): User
    {
        $user = new User();
        $member = new Member();
        $member->package_validity = now()->addDays(30)->toDateString();
        $package = new Package();
        $package->price = $price; $package->active = 1;
        $package->feature_flags = $flags;
        $member->setRelation('package', $package);
        $user->setRelation('member', $member);
        return $user;
    }

    public function test_free_members_see_only_live_listings_at_requested_placement(): void
    {
        $visible = app(AdVisibilityService::class)->visibleFor($this->memberOnPlan(0, []),'discovery');
        $this->assertSame(['Visible in Discovery'], $visible->pluck('title')->all());
    }

    public function test_active_paid_ad_free_members_never_see_sponsors(): void
    {
        DB::table('client_scope_settings')->insert(['key'=>'show_ads_to_paid','value'=>'1']);
        $visible = app(AdVisibilityService::class)->visibleFor(
            $this->memberOnPlan(1000, ['ad_free'=>true]),'discovery');
        $this->assertCount(0,$visible);
    }

    public function test_paid_member_without_ad_free_sees_ads_only_when_enabled_by_admin(): void
    {
        $paid = $this->memberOnPlan(1000, ['ad_free'=>false]);
        $this->assertCount(0,app(AdVisibilityService::class)->visibleFor($paid,'discovery'));
        DB::table('client_scope_settings')->insert(['key'=>'show_ads_to_paid','value'=>'1']);
        $this->assertCount(1,app(AdVisibilityService::class)->visibleFor($paid,'discovery'));
    }
}

