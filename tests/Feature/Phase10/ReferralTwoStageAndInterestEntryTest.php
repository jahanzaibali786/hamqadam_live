<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class ReferralTwoStageAndInterestEntryTest extends TestCase
{
    public function test_listing_and_profile_use_same_explicit_interest_approval_workflow(): void
    {
        foreach ([
            resource_path('views/frontend/member/member_listing/partials/scripts.blade.php'),
            resource_path('views/frontend/member/public_profile/index.blade.php'),
        ] as $path) {
            $content=file_get_contents($path);
            $this->assertStringContainsString("route('member.workflow.show'", $content);
            $this->assertStringContainsString('function express_interest(id)', $content);
            $this->assertStringNotContainsString("route('express-interest.store')", $content);
        }
    }

    public function test_package_referral_awards_have_separate_rules_and_exactly_once_checks(): void
    {
        $service=file_get_contents(app_path('Services/Referral/PaidPackageReferralRewardService.php'));
        foreach ([
            'referral_package_activated', "payment_status !== 'Paid'",
            'RegistrationReward::basicPackage()', "where('reference_type','referred_user')",
            "where('reference_id',$buyer->id)", "increment('remaining_interest'",
            'NotificationHelper::event',
        ] as $needle) $this->assertStringContainsString($needle,$service);
        $registration=file_get_contents(app_path('Services/Referral/ReferralRewardService.php'));
        $this->assertStringContainsString('referral_registration_reward',$registration);
        $this->assertStringContainsString('package_coins_earned',$registration);
    }

    public function test_both_payment_paths_credit_after_success_and_admin_settings_are_distinct(): void
    {
        $web=file_get_contents(app_path('Http/Controllers/PackagePaymentController.php'));
        $api=file_get_contents(app_path('Services/Api/V1/Payment/PaymentService.php'));
        $this->assertGreaterThanOrEqual(2,substr_count($web,'PaidPackageReferralRewardService::class'));
        $this->assertStringContainsString('PaidPackageReferralRewardService::class',$api);
        $admin=file_get_contents(app_path('Http/Controllers/ReferralSystemController.php'));
        $this->assertStringContainsString('save_package_reward_settings',$admin);
        $this->assertStringContainsString('save_reward_settings',$admin);
        $this->assertStringContainsString('admin.referral.index',$admin);
        $view=file_get_contents(resource_path('views/frontend/member/referrals/index.blade.php'));
        $this->assertStringContainsString('Two Ways to Earn Referral Coins',$view);
    }
}
