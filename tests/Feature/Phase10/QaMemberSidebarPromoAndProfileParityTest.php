<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaMemberSidebarPromoAndProfileParityTest extends TestCase {
 public function test_sidebar_pins_logout_after_contextual_promo_outside_scroll_area():void {
  $source=file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
  $this->assertStringContainsString('hm-member-context-promo',$source);
  $this->assertStringContainsString('hm-member-sidebar-logout',$source);
  $this->assertGreaterThan(strpos($source,'hq-member-side-scroll'),strpos($source,'hm-member-context-promo'));
  $this->assertGreaterThan(strpos($source,'hm-member-context-promo'),strpos($source,'hm-member-sidebar-logout'));
  $this->assertStringContainsString("route('user.logout')",$source);
 }
 public function test_profile_steps_retain_original_forms_and_preview():void {
  $source=file_get_contents(resource_path('views/frontend/member/profile/index.blade.php'));
  $this->assertStringContainsString('hm-profile-parity-grid',$source);
  $this->assertStringContainsString('hm-profile-parity-steps',$source);
  $this->assertStringContainsString('hm-profile-parity-side',$source);
  $this->assertStringContainsString("route('member.introduction.update'",$source);
  $this->assertStringContainsString("include('frontend.member.profile.basic_info')",$source);
  $this->assertStringContainsString("include('frontend.member.profile.partner_expectation')",$source);
 }
 public function test_promo_illustration_is_original_svg_asset():void {
  $this->assertFileExists(public_path('assets/img/member-reference-couple.svg'));
  $this->assertStringContainsString('hamqadam-member-parity.css',file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php')));
 }
}
