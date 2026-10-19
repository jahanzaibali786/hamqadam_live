<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use Tests\TestCase;

final class QaMemberSidebarScrollTest extends TestCase
{
    public function test_member_sidebar_has_a_separate_scroll_region(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        $this->assertStringContainsString('id="hq-member-side-scroll"', $blade);
        $this->assertStringContainsString('aria-label="Member dashboard navigation"', $blade);
        $css = file_get_contents(public_path('assets/css/hamqadam-member-sidebar-scroll.css'));
        $this->assertStringContainsString('overflow-y:auto', $css);
        $this->assertStringContainsString('max-height:calc(100dvh', $css);
    }

    public function test_member_layout_loads_active_item_script(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hamqadam-member-sidebar-scroll.css', $blade);
        $this->assertStringContainsString('hamqadam-member-sidebar-scroll.js', $blade);
        $js = file_get_contents(public_path('assets/js/hamqadam-member-sidebar-scroll.js'));
        $this->assertStringContainsString('aria-current', $js);
        $this->assertStringContainsString('region.scrollTop', $js);
        $this->assertStringContainsString('currentUrl', $js);
    }
}
