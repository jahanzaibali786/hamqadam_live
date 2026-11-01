<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class WhyMatchLiveRanking404Test extends TestCase
{
    public function test_match_details_supports_live_unsaved_match_and_privacy_filters(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/MemberMatchDetailController.php'));
        $this->assertStringContainsString("where('match_id', $person->id)->first()", $controller);
        $this->assertStringContainsString('compatibilityPreview($viewer, $person)', $controller);
        $this->assertStringContainsString("new ProfileMatch([", $controller);
        $this->assertStringContainsString("setRelation('matchedUser', $person)", $controller);
        foreach (["where('approved', 1)", "where('blocked', 0)", "where('deactivated', 0)", "where('hide_profile', 0)", "where('invisible_mode', true)", "HiddenProfileUser", "IgnoredUser"] as $check) {
            $this->assertStringContainsString($check, $controller);
        }
    }

    public function test_existing_why_this_match_view_and_route_remain_in_use(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/MemberMatchDetailController.php'));
        $routes=file_get_contents(base_path('routes/web.php'));
        $view=file_get_contents(resource_path('views/frontend/member/match_explanation.blade.php'));
        $this->assertStringContainsString("view('frontend.member.match_explanation'", $controller);
        $this->assertStringContainsString("name('member.match.why')", $routes);
        $this->assertStringContainsString('Compatibility Overview', $view);
        $this->assertStringContainsString('AI Match Summary', $view);
        $this->assertStringContainsString('Additional Insights', $view);
        $this->assertStringContainsString('Shared Interests', $view);
    }
}
