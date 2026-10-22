<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaMemberSidebarViewportSizingTest extends TestCase {
 public function test_sidebar_has_explicit_viewport_height_and_flexible_nav_area():void {
  $css=file_get_contents(public_path('assets/css/hamqadam-sidebar-viewport-fix.css'));
  $this->assertStringContainsString('height:calc(100dvh - 69px)!important',$css);
  $this->assertStringContainsString('#hq-member-side-scroll',$css);
  $this->assertStringContainsString('flex:1 1 auto!important',$css);
  $this->assertStringContainsString('overflow-y:auto!important',$css);
  $this->assertStringNotContainsString('grid-template-rows:',$css);
 }
 public function test_stylesheet_version_is_bumped_for_cache_refresh():void {
  $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
  $this->assertStringContainsString("hamqadam-sidebar-viewport-fix.css') }}?v=2",$layout);
 }
}
