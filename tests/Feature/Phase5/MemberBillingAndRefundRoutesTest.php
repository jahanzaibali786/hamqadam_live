<?php

declare(strict_types=1);

namespace Tests\Feature\Phase5;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MemberBillingAndRefundRoutesTest extends TestCase
{
    public function test_referral_dashboard_and_refund_pages_are_separate_authenticated_member_destinations(): void
    {
        foreach (['member.referrals.index', 'member.payment_refunds.index'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $this->assertContains('GET', $route->methods());
            foreach (['auth', 'member', 'verified'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }
    }

    public function test_refund_submission_and_review_are_post_only_and_protected(): void
    {
        $member = Route::getRoutes()->getByName('member.payment_refunds.store');
        $this->assertNotNull($member);
        $this->assertContains('POST', $member->methods());
        $this->assertContains('auth', $member->gatherMiddleware());
        $this->assertContains('member', $member->gatherMiddleware());

        $admin = Route::getRoutes()->getByName('admin.payment_refunds.review');
        $this->assertNotNull($admin);
        $this->assertContains('POST', $admin->methods());
        $this->assertContains('auth', $admin->gatherMiddleware());
        $this->assertContains('admin', $admin->gatherMiddleware());
    }

    public function test_refund_mobile_endpoints_require_authentication(): void
    {
        foreach (['api.v1.payments.refunds.index', 'api.v1.payments.refunds.store'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
    }

    public function test_referral_and_refund_pages_are_member_panel_views(): void
    {
        foreach (['frontend.member.referrals.index', 'frontend.member.billing.refunds'] as $viewName) {
            $this->assertTrue(view()->exists($viewName), $viewName);
        }
    }
}
