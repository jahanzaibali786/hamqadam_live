<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;

class MemberCardAdvisorNotificationRegressionTest extends TestCase
{
    public function test_matched_and_discovery_cards_share_readable_actions(): void
    {
        $matched=file_get_contents(resource_path('views/frontend/member/matched_profiles.blade.php'));
        $listing=file_get_contents(resource_path('views/frontend/member/member_listing/partials/content.blade.php'));
        $css=file_get_contents(public_path('assets/css/hamqadam-member-card-action-fix-20261102.css'));
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString('hm-matched-card-grid',$matched);
        $this->assertStringContainsString('frontend.member.partials.match_actions',$matched);
        $this->assertStringContainsString('frontend.member.partials.match_actions',$listing);
        $this->assertStringContainsString('min(100%,325px)',$css);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))!important',$css);
        $this->assertStringContainsString('hamqadam-member-card-action-fix-20261102.css',$layout);
    }
    public function test_advisor_reply_is_notified_and_unread_until_member_opens_expert(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/MvpAdminController.php'));
        $member=file_get_contents(app_path('Http/Controllers/MemberExpertQuestionController.php'));
        $sidebar=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        $service=file_get_contents(app_path('Services/AdvisorReplyUnreadService.php'));
        $events=file_get_contents(config_path('notification_events.php'));
        $this->assertStringContainsString('advisor_expert_replied',$controller);
        $this->assertStringContainsString('advisor_expert_replied',$events);
        $this->assertStringContainsString('member_read_at',$service);
        $this->assertStringContainsString('markRead((int)$request->user()->id)',$member);
        $this->assertStringContainsString('advisorUnread',$sidebar);
        $this->assertStringContainsString('where(\'q.user_id\',$userId)', $service);
    }
}
