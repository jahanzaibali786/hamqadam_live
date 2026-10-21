<?php
declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class QaSuggestedMatchesProfileAccessTest extends TestCase
{
    public function test_suggested_matches_exclude_zero_compatibility_before_pagination(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClientScope/ClientScopeController.php'));
        $this->assertStringContainsString("->where('match_percentage', '>', 0)", $controller);
        $this->assertStringContainsString("->whereHas('matchedUser'", $controller);
        $this->assertStringContainsString("->orderByDesc('match_percentage')", $controller);
    }

    public function test_match_names_link_to_same_profile_view_route_as_active_members(): void
    {
        $view = file_get_contents(resource_path('views/client_scope/portal.blade.php'));
        $discovery = file_get_contents(resource_path('views/frontend/member/member_listing/index.blade.php'));
        $this->assertStringContainsString('first_name', $view);
        $this->assertStringContainsString('last_name', $view);
        $this->assertStringContainsString("route('member_profile', \$matchedMember->id)", $view);
        $this->assertStringContainsString("route('member_profile', \$user->id)", $discovery);
        $route = Route::getRoutes()->getByName('member_profile');
        $this->assertNotNull($route);
        $this->assertStringContainsString('view_member_profile', $route->getActionName());
    }

    public function test_suggested_match_view_uses_discovery_membership_gate(): void
    {
        $view = file_get_contents(resource_path('views/client_scope/portal.blade.php'));
        $this->assertStringContainsString("get_setting('full_profile_show_according_to_membership')", $view);
        $this->assertStringContainsString('get_remaining_package_value', $view);
        $this->assertStringContainsString('ProfileViewer::query()', $view);
        $this->assertStringContainsString("route('packages')", $view);
    }
}
