<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
final class QaMemberWorkspaceAndNotificationSchemaTest extends TestCase {
 public function test_member_layout_uses_independent_viewport_scroll_styles():void {
  $view=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
  $this->assertStringContainsString('hamqadam-member-workspace-scroll.css',$view);
  $css=file_get_contents(public_path('assets/css/hamqadam-member-workspace-scroll.css'));
  $this->assertStringContainsString('height:100dvh!important',$css);
  $this->assertStringContainsString('overflow-y:auto!important',$css);
  $this->assertStringContainsString('.hm-member-workspace',$css);
  $this->assertStringContainsString('.hq-member-side-scroll',$css);
 }
 public function test_demo_seeder_detects_database_primary_key_type():void {
  $source=file_get_contents(database_path('seeders/HamqadamMemberDesignDemoSeeder.php'));
  $this->assertStringContainsString("Schema::getColumnType('notifications', 'id')",$source);
  $this->assertStringContainsString('if (!$isNumeric)',$source);
 }
}
