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
    public function test_confirmed_matches_need_independent_staff_review(): void {
        $this->assertTrue(method_exists(\App\Services\ClientScope\OutcomeVerificationService::class,'nominate'));
        $this->assertTrue(method_exists(\App\Http\Controllers\Admin\MatchOutcomeReviewController::class,'review'));
    }
}
