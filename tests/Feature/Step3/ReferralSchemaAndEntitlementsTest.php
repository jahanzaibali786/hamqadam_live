<?php

declare(strict_types=1);

namespace Tests\Feature\Step3;

use App\Services\ClientScope\PremiumEntitlementService;
use App\Models\User;
use Tests\TestCase;

class ReferralSchemaAndEntitlementsTest extends TestCase
{
    public function test_paid_features_are_denied_when_member_has_no_active_paid_plan(): void
    {
        $user = new User();
        $user->setRelation('member', null);
        foreach (PremiumEntitlementService::PAID_FEATURES as $feature) {
            $this->assertFalse(app(PremiumEntitlementService::class)->has($user, $feature));
        }
    }

    public function test_entitlements_route_is_registered_behind_sanctum(): void
    {
        $route = app('router')->getRoutes()->getByName('api.v1.bridge.entitlements');
        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
    }
}
