<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Routes/views acceptance smoke checks; no database connection. */
final class QaRouteContractsTest extends TestCase
{
    public function test_admin_config_and_review_routes_are_protected(): void
    {
        foreach ([
            'admin.client.scope.dashboard' => 'GET',
            'admin.client.scope.sponsors.store' => 'POST',
            'admin.client.scope.rewards.rules' => 'POST',
            'admin.client.scope.outcomes.index' => 'GET',
            'admin.client.scope.city_coordinates.store' => 'POST',
            'admin.payment_refunds.index' => 'GET',
            'admin.payment_refunds.review' => 'POST',
        ] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route {$name}");
            $this->assertContains($method, $route->methods());
            $this->assertContains('admin', $route->gatherMiddleware(), "Missing admin protection on {$name}");
        }
    }

    public function test_member_pages_and_mutations_require_authentication(): void
    {
        foreach ([
            'client.scope.portal' => 'GET',
            'client.scope.notifications' => 'GET',
            'client.scope.satisfaction' => 'GET',
            'client.scope.got-match' => 'POST',
            'client.scope.sponsor.impression' => 'POST',
            'client.scope.sponsor.visit' => 'GET',
            'member.payment_refunds.index' => 'GET',
            'member.payment_refunds.store' => 'POST',
        ] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route {$name}");
            $this->assertContains($method, $route->methods());
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_important_demo_views_exist(): void
    {
        foreach ([
            'admin.client_scope.dashboard',
            'admin.client_scope.match_outcomes',
            'admin.payment_refunds.index',
            'client_scope.portal',
            'client_scope.support',
            'client_scope.satisfaction',
            'client_scope.notifications',
            'frontend.member.billing.refunds',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Missing view {$view}");
        }
    }
}

