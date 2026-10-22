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
    public function test_member_shell_has_independent_navigation_and_search(): void {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString("member_dashboard_shell", $layout);
        $this->assertStringContainsString('hm-member-topbar-search', $layout);
        $this->assertStringContainsString('hm-member-mobile-menu', $layout);
        $this->assertStringContainsString('hm-sidebar-column', $layout);
        $public=file_get_contents(resource_path('views/frontend/layouts/app.blade.php'));
        $this->assertStringContainsString("hasSection('member_dashboard_shell')", $public);
        $this->assertStringContainsString('hm-member-overlay', $layout);
    }

    public function test_reference_profile_grids_keep_business_actions(): void {
        $shortlist=file_get_contents(resource_path('views/frontend/member/my_shortlists.blade.php'));
        $match=file_get_contents(resource_path('views/frontend/member/matched_profiles.blade.php'));
        $this->assertStringContainsString('hm-profiles-grid',$shortlist);
        $this->assertStringContainsString('remove_shortlist(', $shortlist);
        $this->assertStringContainsString("route('member_profile'", $shortlist);
        $this->assertStringContainsString('hm-profiles-grid',$match);
        $this->assertStringContainsString("route('match.refresh')", $match);
        $this->assertStringContainsString('express_interest(', $match);
    }

    public function test_staging_scenario_extends_demo_seeder_only():void {
        $source=file_get_contents(database_path('seeders/HamqadamMemberDesignActivitySeeder.php'));
        $this->assertStringContainsString('HamqadamQaGuard::assertSafe()', $source);
        $this->assertStringContainsString('express_interests', $source);
        $this->assertStringContainsString('chat_threads', $source);
        $entry=file_get_contents(database_path('seeders/HamqadamMemberDesignDemoSeeder.php'));
        $this->assertStringContainsString('HamqadamMemberDesignActivitySeeder::class',$entry);
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
