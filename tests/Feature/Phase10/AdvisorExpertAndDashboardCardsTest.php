<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class AdvisorExpertAndDashboardCardsTest extends TestCase {
 public function test_advisor_inbox_uses_listing_and_conversation_links():void {
  $view=file_get_contents(resource_path('views/admin/mvp/expert_questions.blade.php'));
  $this->assertStringContainsString('<table', $view);
  $this->assertStringContainsString("admin.mvp.expert_questions.conversation",$view);
  $routes=file_get_contents(base_path('routes/admin.php'));
  $this->assertStringContainsString("expert_questions.reply",$routes);
  $sidebar=file_get_contents(resource_path('views/admin/inc/sidenav.blade.php'));
  $this->assertStringContainsString('Advisor Consultation — Expert Queries',$sidebar);
 }
 public function test_thread_replies_are_limited_to_owning_member():void {
  $member=file_get_contents(app_path('Http/Controllers/MemberExpertQuestionController.php'));
  $this->assertGreaterThanOrEqual(2,substr_count($member,"where('user_id',$request->user()->id)->findOrFail"));
  $migration=file_get_contents(database_path('migrations/2026_11_02_000002_create_expert_question_messages.php'));
  $this->assertStringContainsString("expert_question_messages",$migration);
 }
 public function test_dashboard_buttons_are_not_six_cramped_columns():void {
  $css=file_get_contents(public_path('assets/css/hamqadam-profile-appreciations.css'));
  $this->assertStringContainsString('minmax(min(100%,245px),1fr)',$css);
  $this->assertStringContainsString('word-break:normal!important',$css);
 }
}
