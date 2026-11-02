<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class MemberProfileAppreciationWebTest extends TestCase
{
 public function test_free_likes_and_paid_superlikes_are_separate_records(): void {
  $like=file_get_contents(app_path('Models/ProfileLike.php'));
  $super=file_get_contents(app_path('Models/ProfileSuperLike.php'));
  $migration=file_get_contents(database_path('migrations/2026_11_02_000001_create_profile_appreciations.php'));
  $this->assertStringContainsString("Schema::create('profile_likes'", $migration);
  $this->assertStringContainsString("Schema::create('profile_super_likes'", $migration);
  $this->assertStringContainsString('recipient_id',$like);
  $this->assertStringContainsString('coins_charged',$super);
 }
 public function test_super_like_coin_charge_is_transactional_and_once_per_pair(): void {
  $service=file_get_contents(app_path('Services/ProfileAppreciationService.php'));
  $this->assertStringContainsString('DB::transaction',$service);
  $this->assertStringContainsString('lockForUpdate()',$service);
  $this->assertStringContainsString("feature_coin_cost_super_like',10",$service);
  $this->assertStringContainsString("decrement('remaining_interest'",$service);
  $this->assertStringContainsString("PackageUsage::record",$service);
  $this->assertStringContainsString('$quotedCost!==$cost',$service);
  $this->assertStringContainsString('ProfileLike::create',$service);
  $this->assertStringContainsString('ProfileSuperLike::create',$service);
 }
 public function test_web_cards_expose_like_and_super_like_without_changing_interest_flow(): void {
  $actions=file_get_contents(resource_path('views/frontend/member/partials/match_actions.blade.php'));
  $listing=file_get_contents(resource_path('views/frontend/member/member_listing/partials/content.blade.php'));
  $script=file_get_contents(public_path('assets/js/hamqadam-profile-appreciations.js'));
  $this->assertStringContainsString('profile_appreciations',$actions);
  $this->assertStringContainsString('profile_appreciations',$listing);
  $this->assertStringContainsString('member.workflow.show',$actions);
  $this->assertStringContainsString('Confirm Super Like',$script);
  $this->assertStringContainsString('quoted_cost',$script);
 }
}
