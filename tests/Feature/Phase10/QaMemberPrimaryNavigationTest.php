<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class QaMemberPrimaryNavigationTest extends TestCase
{
    public function test_member_shell_has_five_live_primary_destinations(): void
    {
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        foreach (['dashboard','member.listing','member.reference.packages','member.reference.stories','client.scope.support'] as $route) {
            $this->assertStringContainsString("'route' => '".$route."'", $layout);
        }
        $this->assertStringContainsString('aria-current="page"', $layout);
        $this->assertStringContainsString('hm-member-primary-nav', $layout);
    }
    public function test_logged_in_discovery_uses_the_same_shell(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/member_listing/index.blade.php'));
        $this->assertStringContainsString("@extends('frontend.layouts.member_panel')", $blade);
        $this->assertStringContainsString("@section('panel_content')", $blade);
    }
    public function test_home_keeps_live_recommendations_and_privacy_checks(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/dashboard.blade.php'));
        $this->assertStringContainsString('$similar_profiles', $blade);
        $this->assertStringContainsString('show_profile_picture($candidate)', $blade);
        $this->assertStringContainsString('get_remaining_package_value', $blade);
        $this->assertStringContainsString('Profile Completion', $blade);
        $this->assertStringNotContainsString('Sara, 27', $blade);
    }
}
