<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class AdminReferralSettingsNavigationTest extends TestCase
{
    public function test_referral_settings_lives_within_main_settings_group(): void
    {
        $sidebar = file_get_contents(resource_path('views/admin/inc/sidenav.blade.php'));
        $settings = substr($sidebar, strpos($sidebar, '<!-- General settings -->'));
        $this->assertStringContainsString("route('admin.referrals.settings')", $settings);
        $this->assertStringContainsString('>Referral Settings</span>', $settings);
        $this->assertStringContainsString("user_type === 'admin'", $settings);
        $this->assertStringNotContainsString("can('set_referral_commission')", $settings);
    }
    public function test_referral_reward_settings_routes_are_admin_only(): void
    {
        $routes = file_get_contents(base_path('routes/referral.php'));
        $this->assertStringContainsString("middleware(['auth', 'admin'])", $routes);
        $this->assertStringContainsString("name('admin.referrals.settings')", $routes);
        $controller = file_get_contents(app_path('Http/Controllers/ReferralSystemController.php'));
        $this->assertStringContainsString('function authorizeRewardSettings()', $controller);
        $this->assertStringContainsString("\$user->user_type === 'admin'", $controller);
        $this->assertStringNotContainsString("\$user->can('set_referral_commission')", $controller);
        $this->assertGreaterThanOrEqual(3, substr_count($controller, '$this->authorizeRewardSettings()'));
    }
}
