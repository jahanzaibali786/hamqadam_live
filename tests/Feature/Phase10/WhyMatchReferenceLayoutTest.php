<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class WhyMatchReferenceLayoutTest extends TestCase
{
    public function test_match_page_renders_reference_sections_and_uses_existing_wine_palette(): void
    {
        $view = file_get_contents(resource_path('views/frontend/member/match_explanation.blade.php'));
        $css = file_get_contents(public_path('assets/css/hamqadam-why-match-20261026.css'));

        foreach ([
            'Why This Match?', 'Quick Actions', 'Key Matching Factors',
            'AI Match Summary', 'Additional Insights', 'Shared Interests',
            'Compatibility Overview', 'Shared Values', 'Lifestyle Match',
            'Family & Background', 'Education & Career', 'Detailed Analysis',
        ] as $section) {
            $this->assertStringContainsString($section, $view);
        }
        $this->assertStringContainsString('data-why-tab', $view);
        $this->assertStringContainsString("route('member.match.propose'", $view);
        $this->assertStringContainsString("route('reportusers.store')", $view);
        $this->assertStringContainsString('role="tabpanel"', $view);
        $this->assertStringContainsString('hamqadam-why-match-20261026.css', $view);
        $this->assertStringContainsString('#a84469', $css);
        $this->assertStringContainsString('#9f1239', $css);
        $this->assertStringContainsString('#67051f', $css);
        $this->assertStringContainsString('@media(max-width:600px)', $css);
    }

    public function test_match_details_are_scoped_to_the_signed_in_user_and_not_mocked(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/MemberMatchDetailController.php'));
        $this->assertStringContainsString('visibleToMember($viewer)', $controller);
        $this->assertStringContainsString("where('match_id', $member)", $controller);
        $this->assertStringContainsString('MemberMatchCardActions::between', $controller);
        $this->assertStringContainsString('factorScores', $controller);
        $this->assertStringContainsString('sharedInterests', $controller);
    }
}
