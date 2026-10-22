<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaMemberHeaderBlogsNavigationTest extends TestCase
{
    public function test_header_contains_integrated_nav_and_accessible_search_overlay(): void
    {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        foreach (['member.reference.home','member.listing','member.reference.packages','member.reference.stories','client.scope.support'] as $route) {
            $this->assertStringContainsString("'route' => '".$route."'",$layout);
        }
        $this->assertStringContainsString('hm-integrated-search-panel',$layout);
        $this->assertStringContainsString('hm-integrated-search-close',$layout);
        $this->assertStringNotContainsString('<nav class="hm-member-primary-nav"',$layout);
    }

    public function test_editorial_routes_are_member_specific(): void
    {
        $routes=file_get_contents(base_path('routes/member_reference.php'));
        $this->assertStringContainsString("->name('blog.details')",$routes);
        $this->assertStringContainsString("->name('story.details')",$routes);
        $wallet=file_get_contents(resource_path('views/frontend/member/wallet/index.blade.php'));
        $this->assertStringContainsString("route('member.reference.packages')",$wallet);
        $this->assertStringNotContainsString("route('packages')",$wallet);
    }

    public function test_seed_has_no_production_guard_and_is_idempotent(): void
    {
        $seed=file_get_contents(database_path('seeders/HamqadamEditorialBlogSeeder.php'));
        $this->assertStringContainsString("where('slug',$".'slug)',$seed);
        $this->assertStringContainsString("'status'=>1",$seed);
        $this->assertStringNotContainsString('assertSafe()',$seed);
        $this->assertStringContainsString('if(DB::table',$seed);
    }
}
