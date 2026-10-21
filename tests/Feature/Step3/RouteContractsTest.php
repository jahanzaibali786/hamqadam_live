<?php

declare(strict_types=1);

namespace Tests\Feature\Step3;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteContractsTest extends TestCase
{
    public function test_payment_retry_uses_authenticated_post(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.payments.checkout.retry');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
    }
    public function test_web_refund_requests_use_member_authentication(): void
    {
        foreach (['member.payment_refunds.index','member.payment_refunds.store'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            foreach (['auth','member','verified'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }
    }
    public function test_admin_refund_review_requires_admin(): void
    {
        $route = Route::getRoutes()->getByName('admin.payment_refunds.review');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('admin', $route->gatherMiddleware());
    }
}
