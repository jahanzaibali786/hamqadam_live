<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;

final class QaMemberReferenceProfileReviewTest extends TestCase
{
    public function test_profile_reference_tabs_preserve_all_existing_edit_forms(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/profile/index.blade.php'));
        $this->assertStringContainsString('hm-profile-ref-tabs',$blade);
        $this->assertStringContainsString('hm-profile-ref-content',$blade);
        $this->assertStringContainsString("route('member.introduction.update'",$blade);
        $this->assertStringContainsString("route('user.change.email')",$blade);
        $this->assertStringContainsString("include('frontend.member.profile.basic_info')",$blade);
        $this->assertStringContainsString("include('frontend.member.profile.education.index')",$blade);
        $this->assertStringContainsString("data-group=\"preferences\"",$blade);
    }
    public function test_sidebar_can_extend_with_document(): void
    {
        $css=file_get_contents(public_path('assets/css/hamqadam-member-reference-v2.css'));
        $this->assertStringContainsString('height:auto!important',$css);
        $this->assertStringContainsString('max-height:none!important',$css);
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hamqadam-member-reference-v2.css',$layout);
    }
}
