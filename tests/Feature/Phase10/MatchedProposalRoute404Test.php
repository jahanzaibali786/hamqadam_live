<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class MatchedProposalRoute404Test extends TestCase {
 public function test_proposal_preview_supports_get_and_legacy_post_without_saved_match_requirement(): void {
  $routes=file_get_contents(base_path('routes/web.php'));
  $controller=file_get_contents(app_path('Http/Controllers/MemberMatchActionController.php'));
  $this->assertStringContainsString("Route::get('/matched-profiles/{member}/proposal'", $routes);
  $this->assertStringContainsString("Route::post('/matched-profiles/{member}/proposal'", $routes);
  $this->assertStringContainsString("function showProposal(Request \$request, int \$member)",$controller);
  $this->assertStringContainsString("route('member.workflow.show', \$member)",$controller);
  $this->assertStringNotContainsString('ProfileMatch::',$controller);
  $this->assertStringContainsString('HiddenProfileUser',$controller);
  $this->assertStringContainsString('IgnoredUser',$controller);
 }
}
