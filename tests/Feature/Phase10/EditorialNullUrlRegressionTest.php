<?php

namespace Tests\Feature\Phase10;

use App\Support\PublicNavigation;
use Tests\TestCase;

class EditorialNullUrlRegressionTest extends TestCase
{
    public function test_our_team_link_is_guarded_for_empty_cms_url(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/editorial/sections/team.blade.php'));
        $this->assertStringContainsString("!empty(\$member['action_link'])", $blade);
        $this->assertStringNotContainsString("url(\$member['action_link'])", $blade);
    }

    public function test_about_primary_link_is_guarded_for_empty_cms_url(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/editorial/sections/about.blade.php'));
        $this->assertStringContainsString("!empty(\$hero['primary_link'])", $blade);
        $this->assertStringNotContainsString("url(\$hero['primary_link'])", $blade);
    }

    public function test_public_navigation_link_always_produces_string(): void
    {
        $this->assertIsString(PublicNavigation::link('/our-team'));
        $this->assertIsString(PublicNavigation::link(''));
        $this->assertIsString(PublicNavigation::link('https://example.com'));
    }
}
