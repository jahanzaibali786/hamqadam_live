<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use Tests\TestCase;

final class QaBrandedErrorPagesTest extends TestCase
{
    public function test_common_http_errors_have_standalone_branded_templates(): void
    {
        foreach ([400,401,403,404,405,408,409,410,413,419,422,423,429,500,502,503,504] as $code) {
            $path=resource_path("views/errors/{$code}.blade.php");
            $this->assertFileExists($path);
            $this->assertStringContainsString("errors.page",file_get_contents($path));
        }
        $this->assertFileExists(resource_path('views/errors/4xx.blade.php'));
        $this->assertFileExists(resource_path('views/errors/5xx.blade.php'));
    }

    public function test_error_layout_does_not_use_database_dependent_frontend_layout(): void
    {
        $blade=file_get_contents(resource_path('views/errors/page.blade.php'));
        $this->assertStringNotContainsString("@extends('frontend.layouts.app')",$blade);
        $this->assertStringContainsString('Hamqadam',$blade);
        $this->assertStringContainsString('viewport',$blade);
        $this->assertStringNotContainsString('get_setting(',$blade);
        $this->assertStringNotContainsString('Auth::',$blade);
    }

    public function test_not_found_response_is_html_and_keeps_404_status(): void
    {
        $response=$this->get('/__hamqadam_unknown_test_url_404__');
        $response->assertStatus(404);
        $response->assertSee('Hamqadam');
        $response->assertSee('Return to homepage');
        $response->assertDontSee('Stack trace');
    }
}
