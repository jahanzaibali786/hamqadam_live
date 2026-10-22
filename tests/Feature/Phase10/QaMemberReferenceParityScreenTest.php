<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaMemberReferenceParityScreenTest extends TestCase {
 public function test_illustrated_member_screen_assets_and_screens_exist():void {
  foreach(['assets/img/member-reference-couple.svg','assets/css/hamqadam-member-parity.css'] as $file)
   $this->assertFileExists(public_path($file));
  foreach(['assistant','blogs','stories','meetings','packages'] as $screen){
   $s=file_get_contents(resource_path('views/frontend/member/reference/'.$screen.'.blade.php'));
   $this->assertStringContainsString("extends('frontend.layouts.member_panel')",$s);
   $this->assertStringContainsString('hm-reference',$s);
  }
 }
 public function test_existing_service_and_purchases_preserved():void {
  $assistant=file_get_contents(resource_path('views/frontend/member/reference/assistant.blade.php'));
  $this->assertStringContainsString("route('member.reference.assistant.run')",$assistant);
  $meeting=file_get_contents(resource_path('views/frontend/member/reference/meetings.blade.php'));
  $this->assertStringContainsString("route('member.reference.meetings.store')",$meeting);
  $this->assertStringContainsString('proposal_id',$meeting);
  $packages=file_get_contents(resource_path('views/frontend/member/reference/packages.blade.php'));
  $this->assertStringContainsString("route('package_payment_methods'",$packages);
  $profile=file_get_contents(resource_path('views/frontend/member/profile/index.blade.php'));
  $this->assertStringContainsString("route('member.introduction.update'",$profile);
  $this->assertStringContainsString("include('frontend.member.profile.partner_expectation')",$profile);
 }
 public function test_stories_keep_encoded_ids_and_blogs_keep_actual_route():void {
  $stories=file_get_contents(resource_path('views/frontend/member/reference/stories.blade.php'));
  $this->assertStringContainsString('StoryPublicId::encode',$stories);
  $blogs=file_get_contents(resource_path('views/frontend/member/reference/blogs.blade.php'));
  $this->assertStringContainsString("route('blog.details'",$blogs);
 }
}
