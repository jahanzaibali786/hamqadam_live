<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Database\Seeders\HamqadamMemberDesignDemoSeeder;
use Tests\TestCase;
final class QaMemberDesignSuiteTest extends TestCase {
    public function test_member_dashboard_preserves_existing_real_business_routes(): void {
        $source=file_get_contents(resource_path('views/frontend/member/dashboard.blade.php'));
        foreach(['dashboard','my_matched_profiles','interest_requests','my_shortlists','wallet.index','profile_settings','all.messages','client.scope.portal','member.v1_dashboard','client.scope.support','member_profile'] as $route) {
            if($route==='dashboard')continue;
            $this->assertStringContainsString("route('{$route}'",$source,"Missing linked member feature: {$route}");
        }
        $this->assertStringContainsString('match_percentage', $source);
        $this->assertStringContainsString('hm-match-score', $source);
        $this->assertStringContainsString('remaining_interest', $source);
        $this->assertStringContainsString("show_profile_picture", $source);
    }
    public function test_staging_seeder_is_explicit_and_production_guarded(): void {
        $this->assertTrue(class_exists(HamqadamMemberDesignDemoSeeder::class));
        $src=file_get_contents(database_path('seeders/HamqadamMemberDesignDemoSeeder.php'));
        $this->assertStringContainsString('HamqadamQaGuard::assertSafe()', $src);
        $this->assertStringContainsString('demo.member.design@hamqadam.test',$src);
        $this->assertStringNotContainsString('payment_status', $src);
        $default=file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringNotContainsString('HamqadamMemberDesignDemoSeeder', $default);
    }
    public function test_reference_style_applies_across_shared_member_layout(): void {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $css=file_get_contents(public_path('assets/css/hamqadam-member-suite.css'));
        $this->assertStringContainsString('hamqadam-member-suite.css',$layout);
        foreach(['.hm-kpis','.hm-match-list','.hm-columns','.aiz-user-panel .table','.aiz-user-sidenav'] as $selector)
            $this->assertStringContainsString($selector, $css);
        $this->assertStringContainsString('hq-member-scroll-shell',
            file_get_contents(resource_path('views/frontend/member/sidebar.blade.php')));
    }
}
