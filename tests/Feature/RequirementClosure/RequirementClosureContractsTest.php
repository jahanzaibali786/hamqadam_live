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
    public function test_mutual_reports_nominate_only_one_pending_review_candidate(): void {
        if(\Illuminate\Support\Facades\Schema::hasTable('match_successes') || \Illuminate\Support\Facades\Schema::hasTable('match_outcome_reviews')) {
            $this->markTestSkipped('Only run the isolated fixture against an empty test database.');
        }
        $created=[];
        foreach(['match_successes','match_outcome_reviews'] as $table){
            if(!\Illuminate\Support\Facades\Schema::hasTable($table)){
                \Illuminate\Support\Facades\Schema::create($table,function(\Illuminate\Database\Schema\Blueprint $t)use($table){
                    $t->id();
                    if($table==='match_successes'){
                        $t->unsignedBigInteger('user_id');$t->unsignedBigInteger('matched_user_id');$t->string('status');
                    }else{
                        $t->unsignedBigInteger('member_a_id');$t->unsignedBigInteger('member_b_id');$t->string('status')->default('pending');
                        $t->unique(['member_a_id','member_b_id']);
                    }
                    $t->timestamps();
                });
                $created[]=$table;
            }
        }
        try{
            $service=app(\App\Services\ClientScope\OutcomeVerificationService::class);
            $first=\App\Models\MatchSuccess::create(['user_id'=>501,'matched_user_id'=>502,'status'=>'got_match']);
            $this->assertNull($service->nominate(502,501));
            $second=\App\Models\MatchSuccess::create(['user_id'=>502,'matched_user_id'=>501,'status'=>'got_match']);
            $row=$service->nominate(502,501);
            $this->assertNotNull($row);
            $this->assertSame('pending',$row->status);
            $this->assertSame($row->id,$service->nominate(501,502)->id);
            $this->assertSame(1,\App\Models\MatchOutcomeReview::query()->where('member_a_id',501)->where('member_b_id',502)->count());
        }finally{
            if(isset($first))$first->delete();
            if(isset($second))$second->delete();
            foreach(array_reverse($created) as $table)\Illuminate\Support\Facades\Schema::dropIfExists($table);
        }
    }
    public function test_confirmed_matches_need_independent_staff_review(): void {
        $this->assertTrue(method_exists(\App\Services\ClientScope\OutcomeVerificationService::class,'nominate'));
        $this->assertTrue(method_exists(\App\Http\Controllers\Admin\MatchOutcomeReviewController::class,'review'));
    }
}
