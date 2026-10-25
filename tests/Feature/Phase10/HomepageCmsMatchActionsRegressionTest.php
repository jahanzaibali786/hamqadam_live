<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class HomepageCmsMatchActionsRegressionTest extends TestCase
{
 public function test_homepage_has_content_editor_and_dynamic_hero_labels(): void {
  $home=file_get_contents(resource_path('views/frontend/index.blade.php'));
  $routes=file_get_contents(base_path('routes/admin.php'));
  $this->assertStringContainsString("HomePageContent::get('hero_kicker')",$home);
  $this->assertStringContainsString("HomePageContent::get('hero_title_first')",$home);
  $this->assertStringContainsString("website.home_content.update",$routes);
 }
 public function test_match_insights_are_scoped_to_signed_in_user(): void {
  $source=file_get_contents(app_path('Http/Controllers/MemberMatchDetailController.php'));
  $this->assertStringContainsString('visibleToMember($request->user())',$source);
  $this->assertStringContainsString("where('match_id',\$member)",$source);
 }
 public function test_proposals_use_existing_interest_workflow(): void {
  $source=file_get_contents(app_path('Http/Controllers/MemberMatchActionController.php'));
  $this->assertStringContainsString('ExpressInterestController::class',$source);
  $this->assertStringContainsString('visibleToMember($viewer)',$source);
 }
 public function test_sidebar_has_no_generic_why_this_match(): void {
  $source=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
  $this->assertStringNotContainsString("['client.scope.portal','Why This Match'",$source);
 }
}
