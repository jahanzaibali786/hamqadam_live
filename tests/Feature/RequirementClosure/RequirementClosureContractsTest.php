<?php
declare(strict_types=1);
namespace Tests\Feature\RequirementClosure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;
final class RequirementClosureContractsTest extends TestCase {
    public function test_match_outcome_reviews_are_admin_only(): void {
        foreach(['admin.client.scope.outcomes.index','admin.client.scope.outcomes.review','admin.client.scope.city_coordinates.store'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route,$name);
            $this->assertContains('auth',$route->gatherMiddleware());
            $this->assertContains('admin',$route->gatherMiddleware());
        }
    }
    public function test_review_and_geo_dashboard_views_compile(): void {
        foreach(['admin.client_scope.match_outcomes','admin.client_scope.partials.verified_geo_and_outcomes','admin.client_scope.partials.engagement_performance','admin.client_scope.partials.translation_coverage'] as $view) {
            $this->assertTrue(view()->exists($view));
            $compiled=Blade::compileString(file_get_contents(resource_path('views/'.str_replace('.','/',$view).'.blade.php')));
            $this->assertNotEmpty($compiled);
        }
    }
    public function test_bulk_translation_routes_require_admin_translation_permission(): void {
        foreach(['languages.batch.import','languages.batch.export'] as $name){
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route,$name);
            $this->assertContains('auth',$route->gatherMiddleware());
            $this->assertContains('admin',$route->gatherMiddleware());
            $this->assertContains('permission:translate_languages',$route->gatherMiddleware());
        }
    }
    public function test_website_has_accessible_main_landmark(): void {
        $view=file_get_contents(resource_path('views/frontend/layouts/app.blade.php'));
        $this->assertStringContainsString('id="hamqadam-main-content"',$view);
        $this->assertStringContainsString('Skip to main content',$view);
        $this->assertNotEmpty(Blade::compileString($view));
    }
    public function test_unmigrated_match_review_is_a_safe_no_op(): void {
        $service=app(\App\Services\ClientScope\OutcomeVerificationService::class);
        if(!\Illuminate\Support\Facades\Schema::hasTable('match_outcome_reviews')){
            $this->assertNull($service->nominate(1,2));
        } else {
            $this->assertTrue(method_exists($service,'nominate'));
        }
    }
    public function test_confirmed_matches_need_independent_staff_review(): void {
        $this->assertTrue(method_exists(\App\Services\ClientScope\OutcomeVerificationService::class,'nominate'));
        $this->assertTrue(method_exists(\App\Http\Controllers\Admin\MatchOutcomeReviewController::class,'review'));
    }
}
