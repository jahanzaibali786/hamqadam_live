<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class SuperLikeQuotedCostRegressionTest extends TestCase
{
 public function test_confirmed_price_is_captured_inside_wallet_transaction(): void
 {
  $source=file_get_contents(app_path('Services/ProfileAppreciationService.php'));
  $start=strpos($source,'public function superLike(');
  $this->assertNotFalse($start);
  $method=substr($source,$start);
  $this->assertStringContainsString('function()use($actor,$recipient,$quotedCost)', $method);
  $this->assertStringContainsString('if($quotedCost!==$cost)', $method);
  $this->assertStringContainsString('lockForUpdate()', $method);
  $this->assertStringContainsString("PackageUsage::record", $method);
 }
}
