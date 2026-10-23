<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaMemberBladeSyntaxAccessibilityTest extends TestCase
{
    public function test_reported_blade_templates_compile(): void
    {
        foreach ([
            'frontend.member.interest_requests',
            'frontend.member.my_interests',
            'frontend.member.matched_profiles',
            'frontend.member.messages.index',
        ] as $view) {
            $file = resource_path('views/'.str_replace('.', '/', $view).'.blade.php');
            $this->assertFileExists($file);
            $compiled = app('blade.compiler')->compileString(file_get_contents($file));
            $temporary = tempnam(sys_get_temp_dir(), 'hamqadam-blade-');
            try {
                file_put_contents($temporary, $compiled);
                $output = [];
                $status = 0;
                exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($temporary).' 2>&1', $output, $status);
                $this->assertSame(0, $status, $view.': '.implode("\n", $output));
            } finally {
                @unlink($temporary);
            }
        }
    }

    public function test_accessibility_is_in_navbar_not_workspace(): void
    {
        $layout = file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hm-accessibility-dropdown', $layout);
        $this->assertStringContainsString('aria-expanded="false"', $layout);
        $this->assertSame(1, substr_count($layout, "@include('frontend.member.partials.accessibility_tools')"));
    }
}
