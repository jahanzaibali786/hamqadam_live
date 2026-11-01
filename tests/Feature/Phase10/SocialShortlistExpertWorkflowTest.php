<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class SocialShortlistExpertWorkflowTest extends TestCase
{
    public function test_social_icons_are_configurable_and_not_hardcoded_to_fake_destinations(): void
    {
        $footer=file_get_contents(resource_path('views/frontend/inc/footer.blade.php'));
        $admin=file_get_contents(resource_path('views/admin/website_settings/footer.blade.php'));
        foreach (['hq_social_facebook','hq_social_instagram','hq_social_x','hq_app_store_url','hq_play_store_url'] as $key) {
            $this->assertStringContainsString($key,$footer);
            $this->assertStringContainsString($key,$admin);
        }
        $this->assertStringContainsString('FILTER_VALIDATE_URL',$footer);
    }

    public function test_shortlist_has_no_interest_or_coin_gate(): void
    {
        $service=file_get_contents(app_path('Services/OpenShortlistService.php'));
        $this->assertStringNotContainsString('ExpressInterest',$service);
        $this->assertStringNotContainsString('remaining_interest',$service);
        $this->assertStringContainsString("HiddenProfileUser",$service);
        $this->assertStringContainsString('lockForUpdate',$service);
        $controller=file_get_contents(app_path('Http/Controllers/ShortlistController.php'));
        $this->assertStringContainsString('OpenShortlistService::class',$controller);
    }

    public function test_expert_question_is_submitted_to_same_backend_admin_reviews(): void
    {
        $member=file_get_contents(app_path('Http/Controllers/MemberExpertQuestionController.php'));
        $admin=file_get_contents(app_path('Http/Controllers/MvpAdminController.php'));
        $sidebar=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        $adminSidebar=file_get_contents(resource_path('views/admin/inc/sidenav.blade.php'));
        $this->assertStringContainsString('ExpertQuestion::create',$member);
        $this->assertStringContainsString('ExpertQuestion::with',$admin);
        $this->assertStringContainsString('member.expert.index',$sidebar);
        $this->assertStringContainsString('Ask Expert Queries',$adminSidebar);
    }
}
