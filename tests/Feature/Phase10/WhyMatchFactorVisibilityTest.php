<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class WhyMatchFactorVisibilityTest extends TestCase {
 public function test_factor_explanations_use_full_grid_rows():void {
  $css=file_get_contents(public_path('assets/css/hamqadam-why-match-ai-factors-20261102.css'));
  $this->assertStringContainsString('grid-column:2 / 4',$css);
  $this->assertStringContainsString('overflow-wrap:break-word!important',$css);
  $this->assertStringContainsString('grid-template-columns:26px minmax(0,1fr) auto',$css);
 }
 public function test_like_and_superlike_have_independent_full_size_grid_cells():void {
  $view=file_get_contents(resource_path('views/frontend/member/match_explanation.blade.php'));
  $this->assertStringContainsString('hm-why26-member-actions',$view);
  $this->assertStringNotContainsString('class="hm-why26-appreciation"',$view);
  $this->assertStringContainsString('data-like-url',$view);
  $this->assertStringContainsString('data-super-url',$view);
  $css=file_get_contents(public_path('assets/css/hamqadam-why-match-ai-factors-20261102.css'));
  $this->assertStringContainsString('repeat(5,minmax(0,1fr))',$css);
 }
}
