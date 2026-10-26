<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaUnifiedPublicProfileDetailTest extends TestCase {
 public function test_public_and_member_detail_use_same_reference_layout():void{
  $main=file_get_contents(resource_path('views/frontend/member/public_profile/index.blade.php'));
  $this->assertStringContainsString("@extends('frontend.layouts.member_panel')",$main);
  $this->assertStringContainsString("@include('frontend.member.public_profile.reference_detail')",$main);
  $this->assertStringContainsString("hm-public-legacy-profile",$main);
  $this->assertFileExists(resource_path('views/frontend/member/public_profile/reference_detail.blade.php'));
 }
 public function test_preserved_member_actions_and_photo_privacy():void{
  $legacy=file_get_contents(resource_path('views/frontend/member/public_profile/index.blade.php'));
  $design=file_get_contents(resource_path('views/frontend/member/public_profile/reference_detail.blade.php'));
  foreach(['express_interest(', 'do_shortlist(', 'remove_shortlist(', 'report_member(', 'ignore_member(', 'show_profile_picture(', 'profile_pic_view_request('] as $fn){
   $this->assertStringContainsString($fn,$legacy);
  }
  $this->assertStringContainsString('show_profile_picture($user)',$design);
  $this->assertStringContainsString('$ownProfile',$design);
  $this->assertStringContainsString("hm-detail-columns",$design);
  $this->assertStringContainsString('hm-detail-show-complete',$design);
 }
 public function test_existing_controller_still_applies_once_only_coin_charge():void{
  $c=file_get_contents(app_path('Http/Controllers/HomeController.php'));
  $this->assertStringContainsString('ProfileViewer::where',$c);
  $this->assertStringContainsString('remaining_profile_viewer_view - 1',$c);
  $this->assertStringContainsString('PackageUsage::record',$c);
 }
}
