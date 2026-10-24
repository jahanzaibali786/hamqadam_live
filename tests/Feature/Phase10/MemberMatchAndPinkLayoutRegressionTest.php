<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class MemberMatchAndPinkLayoutRegressionTest extends TestCase
{
    public function test_dashboard_and_matched_profiles_use_shared_match_scope(): void
    {
        $home = file_get_contents(app_path('Http/Controllers/HomeController.php'));
        $listing = file_get_contents(app_path('Http/Controllers/ProfileMatchController.php'));
        $model = file_get_contents(app_path('Models/ProfileMatch.php'));

        $this->assertStringContainsString('visibleToMember($user)', $home);
        $this->assertStringContainsString('visibleToMember($user)', $listing);
        $this->assertStringContainsString('scopeVisibleToMember', $model);
        $this->assertStringNotContainsString("where('match_percentage', '>=', 50)", substr($listing, strpos($listing,'public function myMatchedProfiles()'), 3500));
    }

    public function test_member_panel_loads_pink_styles_and_keeps_profile_editor_rails(): void
    {
        $layout = file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $profile = file_get_contents(resource_path('views/frontend/member/profile/index.blade.php'));
        $this->assertStringContainsString('hamqadam-member-pink-unification-20261024.css', $layout);
        $this->assertStringContainsString('hm-profile-fixed-rails', $profile);
        $this->assertFileExists(public_path('assets/css/hamqadam-member-pink-unification-20261024.css'));
    }
}
