<?php

declare(strict_types=1);

namespace Tests\Feature\Completion;

use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use App\Services\ClientScope\PremiumEntitlementService;
use Tests\TestCase;

/** In-memory Eloquent relations; does not require live payment configuration. */
class PremiumPolicyTest extends TestCase
{
    public function test_free_plan_cannot_use_paid_features(): void
    {
        $user = new User();
        $member = new Member();
        $member->current_package_id = 1;
        $member->package_validity = now()->addDays(30)->toDateString();
        $package = new Package();
        $package->price = 0;
        $package->active = true;
        $package->feature_flags = ['profile_boost'=>true,'unlimited_messaging'=>true,'advanced_filters'=>true];
        $member->setRelation('package', $package);
        $user->setRelation('member', $member);
        $policy = app(PremiumEntitlementService::class);
        foreach (PremiumEntitlementService::PAID_FEATURES as $feature) $this->assertFalse($policy->has($user,$feature));
    }
    public function test_paid_expired_plan_cannot_use_profile_boost(): void
    {
        $user = new User();
        $member = new Member();
        $member->package_validity = now()->subDays(2)->toDateString();
        $package = new Package();
        $package->price = 1000;
        $package->active = true;
        $package->feature_flags = ['profile_boost'=>true];
        $member->setRelation('package', $package);
        $user->setRelation('member', $member);
        $this->assertFalse(app(PremiumEntitlementService::class)->has($user,'profile_boost'));
    }
}
