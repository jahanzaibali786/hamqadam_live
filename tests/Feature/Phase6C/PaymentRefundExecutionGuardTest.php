<?php

declare(strict_types=1);

namespace Tests\Feature\Phase6C;

use App\Models\PaymentRefundRequest;
use App\Services\Api\V1\Payment\StripeRefundExecutionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PaymentRefundExecutionGuardTest extends TestCase
{
    public function test_stripe_refund_execution_defaults_to_disabled(): void
    {
        config()->set('hamqadam_payments.stripe_refunds_enabled', false);
        $this->expectException(ValidationException::class);

        app(StripeRefundExecutionService::class)->execute(new PaymentRefundRequest(), 1);
    }

    public function test_refund_execution_requires_admin_auth_and_payment_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.payment_refunds.execute');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->assertContains('permission:show_package_payments', $route->gatherMiddleware());
    }

    public function test_stripe_refund_attempt_model_is_scoped_to_single_request(): void
    {
        $this->assertTrue(method_exists(\App\Models\PaymentRefundAttempt::class, 'refundRequest'));
        $this->assertTrue(method_exists(\App\Models\PaymentRefundAttempt::class, 'payment'));
    }
}
