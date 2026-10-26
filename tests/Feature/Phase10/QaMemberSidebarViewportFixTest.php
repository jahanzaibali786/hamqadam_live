<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaMemberSidebarViewportFixTest extends TestCase
{
    public function test_menu_scroll_and_logout_footer_are_distinct(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        $this->assertStringContainsString('id="hq-member-side-scroll"', $blade);
        $this->assertStringContainsString('hm-member-context-promo', $blade);
        $this->assertStringContainsString('hm-member-sidebar-logout', $blade);
        $this->assertLessThan(strpos($blade,'hm-member-context-promo'), strpos($blade,'id="hq-member-side-scroll"'));
        $this->assertLessThan(strpos($blade,'hm-member-sidebar-logout'), strpos($blade,'hm-member-context-promo'));
        $this->assertStringContainsString("route('user.logout')",$blade);
    }
    public function test_reference_nav_contains_core_items_and_more_features(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        foreach(['Dashboard','My Profile','My Interest','Shortlist','Messaging','Matched Profiles','Packages & Plans','Referral Program','Happy Stories','Blogs & Articles','Settings','More Features'] as $label) {
            $this->assertStringContainsString($label,$blade);
        }
        $this->assertStringContainsString('hm-member-extras',$blade);
        $this->assertStringContainsString('unseen-chat-badge-sidebar',$blade);
    }
    public function test_viewport_css_loads_last_and_has_grid_rows(): void
    {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hamqadam-sidebar-viewport-fix.css',$layout);
        $css=file_get_contents(public_path('assets/css/hamqadam-sidebar-viewport-fix.css'));
        $this->assertStringContainsString('grid-template-rows:minmax(0,1fr) auto auto',$css);
        $this->assertStringContainsString('overflow-y:auto',$css);
    }
}
