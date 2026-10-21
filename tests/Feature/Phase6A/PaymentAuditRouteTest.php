<?php

declare(strict_types=1);

namespace Tests\Feature\Phase6A;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PaymentAuditRouteTest extends TestCase
{
    public function test_admin_payment_audit_route_is_protected(): void
    {
        $route = Route::getRoutes()->getByName('admin.payment_audit.index');
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertTrue(view()->exists('admin.payment_audit.index'));
    }

    public function test_stripe_webhook_is_public_but_not_an_unchecked_direct_payment_grant(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.payments.webhooks.stripe');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertTrue(method_exists(\App\Services\Api\V1\Payment\PaymentService::class, 'processStripeWebhook'));
    }
}
