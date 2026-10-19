<?php

declare(strict_types=1);

namespace Tests\Feature\Phase6B;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class DashboardBladeCompilationTest extends TestCase
{
    public function test_dashboard_blade_compiles_to_valid_php(): void
    {
        $path = resource_path('views/frontend/member/dashboard.blade.php');
        $source = file_get_contents($path);
        $this->assertNotFalse($source);
        $this->assertStringContainsString("@section('panel_content')", $source);
        $this->assertSame(substr_count($source, '<?php'), substr_count($source, '?>'));

        $compiled = Blade::compileString($source);
        $temp = tempnam(sys_get_temp_dir(), 'hq-blade-');
        $this->assertNotFalse($temp);
        try {
            file_put_contents($temp, $compiled);
            $output = [];
            $status = 1;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($temp) . ' 2>&1', $output, $status);
            $this->assertSame(0, $status, implode("\n", $output));
        } finally {
            @unlink($temp);
        }
    }
}
