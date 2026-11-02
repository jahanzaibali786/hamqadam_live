<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;

class MatchedProfileCardAlignmentTest extends TestCase
{
    public function test_matched_cards_use_wide_responsive_columns_and_readable_actions(): void
    {
        $css=file_get_contents(public_path('assets/css/hamqadam-profile-appreciations.css'));
        $this->assertStringContainsString('minmax(min(100%, 285px), 1fr)', $css);
        $this->assertStringContainsString('.hm-four .hm-four-matches', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr)) !important', $css);
        $this->assertStringContainsString('word-break: normal !important', $css);
        $this->assertStringContainsString('.hm-profile-appreciations button', $css);
    }

    public function test_cards_use_existing_shared_action_component_without_package_view_redirect(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/matched_profiles.blade.php'));
        $this->assertStringContainsString("frontend.member.partials.match_actions", $view);
        $this->assertStringContainsString("frontend.member.partials.match_dropdown", $view);
        $this->assertStringContainsString("route('member_profile', \$person->id)", $view);
        $this->assertStringNotContainsString('full_profile_show_according_to_membership', $view);
    }
}
