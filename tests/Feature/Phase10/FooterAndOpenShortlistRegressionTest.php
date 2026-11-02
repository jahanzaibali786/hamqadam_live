<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class FooterAndOpenShortlistRegressionTest extends TestCase
{
    public function test_footer_social_directives_are_balanced(): void
    {
        $view=file_get_contents(resource_path('views/frontend/inc/footer.blade.php'));
        $this->assertStringContainsString('@foreach ($footerSocialLinks as $footerSocialLink)', $view);
        $this->assertStringContainsString('@endphp', $view);
        $this->assertSame(substr_count($view, '@foreach'), substr_count($view, '@endforeach'));
        $this->assertSame(substr_count($view, '@if(')+substr_count($view, '@if ('), substr_count($view, '@endif'));
        $this->assertStringNotContainsString('@php(', $view);
    }

    public function test_shortlist_routes_are_outside_package_gate(): void
    {
        $routes=file_get_contents(base_path('routes/web.php'));
        $this->assertSame(1, substr_count($routes, "name('member.add_to_shortlist')"));
        $this->assertSame(1, substr_count($routes, "name('member.remove_from_shortlist')"));
        $this->assertTrue(strpos($routes, "Route::get('/my-shortlists'") < strpos($routes, "['member', 'verified', 'check.package', 'manual.review']"));
        $service=file_get_contents(app_path('Services/OpenShortlistService.php'));
        $this->assertStringNotContainsString('remaining_interest',$service);
        $this->assertStringNotContainsString('feature_coin_cost',$service);
    }

    public function test_member_profile_shortlist_has_no_legacy_package_popup(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/public_profile/index.blade.php'));
        $this->assertStringContainsString('function do_shortlist(id) { do_shortlist_confirm(id); }',$view);
        $this->assertStringNotContainsString("Please express interest first and wait for approval before shortlisting.",$view);
    }
}
