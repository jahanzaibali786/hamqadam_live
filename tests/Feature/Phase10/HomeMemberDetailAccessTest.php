<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class HomeMemberDetailAccessTest extends TestCase
{
    public function test_home_member_cards_use_auth_only_for_detail_links(): void
    {
        $view=file_get_contents(resource_path('views/frontend/inc/member_box_1.blade.php'));
        $this->assertStringContainsString("if(!Auth::check())",$view);
        $this->assertStringContainsString("route('member_profile', \$member->id)",$view);
        $this->assertStringNotContainsString('package_update_alert()', $view);
    }
    public function test_member_profile_details_route_is_outside_package_gate(): void
    {
        $routes=file_get_contents(base_path('routes/web.php'));
        $this->assertSame(1,substr_count($routes,"Route::get('/member-profile/{id}'"));
        $this->assertStringContainsString("['auth', 'member', 'verified', 'manual.review', 'activated']",$routes);
        $this->assertStringContainsString("'check.package', 'manual.review'",$routes);
    }
}
