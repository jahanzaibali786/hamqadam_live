<?php

declare(strict_types=1);

namespace Tests\Feature\Phase8;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class Phase8RouteContractsTest extends TestCase
{
    public function test_admin_notification_audit_route_is_restricted(): void
    {
        $route = Route::getRoutes()->getByName('admin.notification_audit.index');
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }

    public function test_help_agent_assignment_is_admin_post_only(): void
    {
        $route = Route::getRoutes()->getByName('admin.help-chat.assign');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }

    public function test_new_admin_audit_view_exists(): void
    {
        $this->assertTrue(view()->exists('admin.notification_audit.index'));
        $this->assertTrue(view()->exists('admin.help_chat.show'));
    }
}
