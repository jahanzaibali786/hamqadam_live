<?php
namespace Tests\Feature\Phase10;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
class AppreciationApiRoutesTest extends TestCase
{
 public function test_mobile_like_super_like_and_activity_routes_exist(): void {
  $uris=collect(Route::getRoutes()->getRoutes())->map(fn($r)=>implode('|',$r->methods()).' '.$r->uri())->implode("\n");
  $this->assertStringContainsString('GET|HEAD api/v1/appreciations/activity',$uris);
  $this->assertStringContainsString('GET|HEAD api/v1/appreciations/members/{member}',$uris);
  $this->assertStringContainsString('POST api/v1/appreciations/members/{member}/like',$uris);
  $this->assertStringContainsString('POST api/v1/appreciations/members/{member}/super-like',$uris);
 }
 public function test_api_reuses_the_shared_appreciation_service(): void {
  $controller=file_get_contents(app_path('Http/Controllers/Api/V1/Appreciation/AppreciationController.php'));
  $this->assertStringContainsString('ProfileAppreciationService',$controller);
  $this->assertStringContainsString("'quoted_cost'",$controller);
  $this->assertStringContainsString("'in:received,sent'",$controller);
 }
}
