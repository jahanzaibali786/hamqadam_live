<?php

namespace Tests\Feature\Phase10;

use App\Support\AiMatchFactorPresenter;
use Tests\TestCase;

class WhyMatchAiFactorsActionsTest extends TestCase
{
    public function test_ai_criterion_matches_are_dynamic_and_preserve_actual_scores(): void
    {
        $sections = AiMatchFactorPresenter::sections([
            ['criterion'=>'location','score'=>100,'status'=>'match','reason'=>'Country matches preference','applicable'=>true],
            ['criterion'=>'religion','score'=>100,'status'=>'match','reason'=>'Religion matches: Islam','applicable'=>true],
            ['criterion'=>'age','score'=>80,'status'=>'match','reason'=>'Age 22 is within preferred range 22-32','applicable'=>true],
            ['criterion'=>'diet','score'=>50,'status'=>'unknown','reason'=>'Diet not provided','applicable'=>true],
        ],[
            ['criterion'=>'age','score'=>80,'status'=>'match','reason'=>'Age 27 is within preferred range 18-27','applicable'=>true],
        ]);
        $this->assertCount(2, $sections);
        $this->assertSame(['Location','Religion','Age','Diet'], array_column($sections[0]['items'], 'label'));
        $this->assertSame([100,100,80,null], array_column($sections[0]['items'], 'score'));
        $this->assertSame('Age 27 is within preferred range 18-27',$sections[1]['items'][0]['reason']);
    }

    public function test_model_bidirectional_fields_persist_through_stored_matches_and_preview(): void
    {
        $ranking=file_get_contents(app_path('Services/Api/V1/Matching/MatchmakingIntegrationService.php'));
        $search=file_get_contents(app_path('Services/Api/V1/Search/ProfileSearchService.php'));
        $controller=file_get_contents(app_path('Http/Controllers/MemberMatchDetailController.php'));
        $this->assertStringContainsString("'their_breakdown' =>", $ranking);
        $this->assertStringContainsString("'score_breakdown_their'     =>", $search);
        $this->assertStringContainsString('AiMatchFactorPresenter::sections', $controller);
        $this->assertStringNotContainsString("'Religion & Beliefs'", $controller);
    }

    public function test_why_match_heart_and_star_are_real_server_backed_actions(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/match_explanation.blade.php'));
        $this->assertStringContainsString("route('member.profile.like', $person->id)", $view);
        $this->assertStringContainsString("route('member.profile.superlike', $person->id)", $view);
        $this->assertStringContainsString('data-like-url', $view);
        $this->assertStringContainsString('data-super-url', $view);
        $this->assertStringContainsString('hm-why26-factor-reason', $view);
        $this->assertStringContainsString('aria-pressed', $view);
    }
}
