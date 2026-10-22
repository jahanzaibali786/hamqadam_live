<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaMemberHomeReferenceLayoutTest extends TestCase
{
    public function test_home_cards_have_full_blade_directives_and_four_column_reference_grid(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/member/reference/home.blade.php'));
        $css = file_get_contents(public_path('assets/css/hamqadam-unified-header-blog.css'));
        $this->assertStringContainsString('@forelse ($recommended as $match)', $blade);
        $this->assertStringContainsString('@endforelse', $blade);
        $this->assertStringContainsString('hm-home-ref-grid', $blade);
        $this->assertStringContainsString('hm-home-ref-sidebar', $blade);
        $this->assertStringContainsString('grid-template-columns:repeat(4,minmax(0,1fr))', $css);
        $this->assertStringNotContainsString('@php($fallback=', $blade);
        $this->assertStringContainsString("route('member.reference.packages')", $blade);
    }
}
