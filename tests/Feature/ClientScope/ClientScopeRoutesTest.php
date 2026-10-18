<?php

namespace Tests\Feature\ClientScope;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Run against the full Laravel application: php artisan test --filter=ClientScopeRoutesTest */
class ClientScopeRoutesTest extends TestCase
{
    public function test_admin_dashboard_requires_both_auth_and_admin_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('admin.client.scope.dashboard');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }

    public function test_member_routes_require_authentication(): void
    {
        foreach (['client.scope.portal','client.scope.support','client.scope.got-match',
                  'client.scope.satisfaction.submit','client.scope.notifications.save'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, 'Missing route: '.$name);
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_sponsor_impression_is_an_authenticated_post_only_route(): void
    {
        $route = Route::getRoutes()->getByName('client.scope.sponsor.impression');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('POST', $route->methods());
    }

    public function test_support_details_route_requires_authentication(): void
    {
        $route = Route::getRoutes()->getByName('support-tickets.user_view_details');
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
