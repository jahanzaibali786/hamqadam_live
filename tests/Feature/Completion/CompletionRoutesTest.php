<?php

declare(strict_types=1);

namespace Tests\Feature\Completion;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Route/middleware contracts only; full purchases need a MySQL staging DB. */
class CompletionRoutesTest extends TestCase
{
    public function test_paid_boost_api_requires_sanctum_and_http_post(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.bridge.premium.boost.activate');
        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        $this->assertContains('POST', $route->methods());
    }
    public function test_member_boost_is_not_public_and_uses_csrf_protected_web_route(): void
    {
        $route = Route::getRoutes()->getByName('member.profile_boost.activate');
        $this->assertNotNull($route);
        foreach (['auth','member','verified'] as $guard) $this->assertContains($guard, $route->gatherMiddleware());
        $this->assertContains('POST', $route->methods());
    }
    public function test_mobile_entitlements_route_is_guarded(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.bridge.entitlements');
        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
    }
    public function test_existing_member_referral_dashboard_is_guarded(): void
    {
        $route = Route::getRoutes()->getByName('member.referrals.index');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
