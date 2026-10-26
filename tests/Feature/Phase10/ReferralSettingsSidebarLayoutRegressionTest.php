<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class ReferralSettingsSidebarLayoutRegressionTest extends TestCase
{
    public function test_referral_reward_tables_have_a_migration_and_setup_fallback(): void
    {
        $migration=file_get_contents(database_path('migrations/2026_10_26_000004_create_referral_reward_ledger_tables.php'));
        $this->assertStringContainsString("Schema::create('reward_rules'", $migration);
        $this->assertStringContainsString("Schema::create('reward_transactions'", $migration);
        $controller=file_get_contents(app_path('Http/Controllers/ReferralSystemController.php'));
        $this->assertStringContainsString('Schema::hasTable', $controller);
        $this->assertStringContainsString('admin.referral.setup_required', $controller);
    }
    public function test_admin_sidebar_does_not_force_inline_open_heights(): void
    {
        $script=file_get_contents(public_path('assets/js/hamqadam-admin-active-sidebar.js'));
        $this->assertStringNotContainsString("p.style.height='auto'", $script);
        $this->assertStringNotContainsString("p.style.display='block'", $script);
        $css=file_get_contents(public_path('assets/css/hamqadam-admin-sidebar-accordion-20261026.css'));
        $this->assertStringContainsString('.mm-collapsing', $css);
    }
}
