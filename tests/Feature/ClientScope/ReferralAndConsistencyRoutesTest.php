<?php

declare(strict_types=1);

namespace Tests\Feature\ClientScope;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Route-contract tests. Business integration still needs seeded DB tests. */
class ReferralAndConsistencyRoutesTest extends TestCase
{
    public function test_member_referrals_dashboard_requires_authentication_and_member_scope(): void
    {
        $route = Route::getRoutes()->getByName('member.referrals.index');
        $this->assertNotNull($route);
        foreach (['auth', 'member', 'verified'] as $guard) {
            $this->assertContains($guard, $route->gatherMiddleware());
        }
    }

    public function test_admin_referral_rule_is_not_public(): void
    {
        $route = Route::getRoutes()->getByName('admin.referrals.reward_settings');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }

    public function test_mobile_referral_endpoints_inherit_sanctum_guard(): void
    {
        foreach (['summary', 'members', 'rewards'] as $suffix) {
            $route = Route::getRoutes()->getByName('api.v1.bridge.referrals.'.$suffix);
            $this->assertNotNull($route, 'Missing referral route '.$suffix);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
    }

    public function test_web_and_mobile_got_match_are_post_actions(): void
    {
        $web = Route::getRoutes()->getByName('client.scope.got-match');
        $api = Route::getRoutes()->getByName('api.v1.completion.got_match.store');
        $this->assertNotNull($web);
        $this->assertNotNull($api);
        $this->assertContains('POST', $web->methods());
        $this->assertContains('POST', $api->methods());
        $this->assertContains('auth', $web->gatherMiddleware());
        $this->assertContains('auth:sanctum', $api->gatherMiddleware());
    }
}
