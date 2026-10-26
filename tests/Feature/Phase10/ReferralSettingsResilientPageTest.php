<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class ReferralSettingsResilientPageTest extends TestCase
{
    public function test_referral_settings_are_independent_of_report_queries_and_show_schema_diagnostics(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/ReferralSystemController.php'));
        $this->assertStringContainsString("response()->view('admin.referral.settings'", $controller);
        $this->assertStringContainsString("Schema::hasTable", $controller);
        $this->assertStringContainsString("Schema::hasColumn", $controller);
        $this->assertStringContainsString("'databaseReady' =>", $controller);
        $view=file_get_contents(resource_path('views/admin/referral/settings.blade.php'));
        $this->assertStringContainsString('Registration Reward', $view);
        $this->assertStringContainsString('Paid Package Activation Reward', $view);
        $this->assertStringContainsString('@unless($databaseReady)', $view);
        $this->assertStringContainsString("route('admin.referrals.package_reward_settings')", $view);
    }
}
