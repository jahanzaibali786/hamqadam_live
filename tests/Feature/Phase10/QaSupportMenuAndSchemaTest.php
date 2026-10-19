<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class QaSupportMenuAndSchemaTest extends TestCase
{
    public function test_client_support_and_admin_ticket_routes_exist(): void
    {
        $member = Route::getRoutes()->getByName('client.scope.support');
        $admin = Route::getRoutes()->getByName('support-tickets.index');
        $this->assertNotNull($member);
        $this->assertNotNull($admin);
        $this->assertContains('auth', $member->gatherMiddleware());
        $this->assertContains('auth', $admin->gatherMiddleware());
        $this->assertContains('admin', $admin->gatherMiddleware());
    }

    public function test_member_pages_share_the_actual_member_sidebar(): void
    {
        foreach (['portal', 'support', 'notifications', 'satisfaction'] as $page) {
            $path = resource_path("views/client_scope/{$page}.blade.php");
            $this->assertFileExists($path);
            $this->assertStringContainsString(
                "@extends('frontend.layouts.member_panel')",
                file_get_contents($path),
            );
        }
    }

    public function test_client_feature_entries_are_present_in_dashboard_menus(): void
    {
        $member = file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        foreach (['client.scope.portal', 'client.scope.notifications', 'client.scope.support', 'client.scope.satisfaction'] as $route) {
            $this->assertStringContainsString("route('{$route}')", $member);
        }
        $admin = file_get_contents(resource_path('views/admin/inc/sidenav.blade.php'));
        foreach (['admin.client.scope.dashboard', 'admin.client.scope.profiles', 'admin.client.scope.outcomes.index','support-tickets.index'] as $route) {
            $this->assertStringContainsString("route('{$route}')", $admin);
        }
    }

    public function test_missing_addon_support_page_has_graceful_fallback(): void
    {
        $blade = file_get_contents(resource_path('views/client_scope/support.blade.php'));
        $this->assertStringContainsString('@if(!$supportReady)', $blade);
        $this->assertFileExists(resource_path('views/admin/support_tickets/dashboard.blade.php'));
        $this->assertFileExists(database_path('migrations/2026_10_19_000029_restore_optional_support_ticket_tables.php'));
    }
}
