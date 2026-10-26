<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class MemberWinePaletteAuditTest extends TestCase
{
    public function test_member_shell_loads_palette_after_old_component_styles(): void
    {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hamqadam-member-brand-palette-20261026.css',$layout);
        $this->assertGreaterThan(
            strpos($layout,'hamqadam-member-wine-theme-20261025.css'),
            strpos($layout,'hamqadam-member-brand-palette-20261026.css')
        );
    }

    public function test_member_palette_contains_required_gradient_and_text_color(): void
    {
        $css=file_get_contents(public_path('assets/css/hamqadam-member-brand-palette-20261026.css'));
        $this->assertStringContainsString('linear-gradient(135deg,#9f1239,#67051f)!important',$css);
        $this->assertStringContainsString('color:#a84469!important',$css);
        $this->assertStringContainsString('.hm-member-app',$css);
        $this->assertStringNotContainsString('#ed2479',$css);
    }

    public function test_old_member_pink_control_palette_no_longer_uses_hot_pink_tokens(): void
    {
        $css=file_get_contents(public_path('assets/css/hamqadam-member-pink-unification-20261024.css'));
        $this->assertStringNotContainsString('--hm-control-pink:#ed2479',$css);
        $this->assertStringNotContainsString('--hm-control-pink-hover:#c91561',$css);
    }
}
