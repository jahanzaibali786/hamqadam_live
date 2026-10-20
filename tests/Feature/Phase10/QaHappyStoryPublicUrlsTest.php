<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use App\Support\StoryPublicId;
use Tests\TestCase;

final class QaHappyStoryPublicUrlsTest extends TestCase
{
    public function test_story_public_id_roundtrip_and_rejects_numeric_or_invalid_tokens(): void
    {
        $token=StoryPublicId::encode(37);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/D',$token);
        $this->assertSame(37,StoryPublicId::decode($token));
        $this->assertNull(StoryPublicId::decode('37'));
        $this->assertNull(StoryPublicId::decode('not-a-valid-token'));
    }

    public function test_listing_and_detail_use_new_responsive_story_styles(): void
    {
        $index=file_get_contents(resource_path('views/frontend/happy_stories/index.blade.php'));
        $detail=file_get_contents(resource_path('views/frontend/happy_stories/story_details.blade.php'));
        $this->assertStringContainsString('StoryPublicId::encode', $index);
        $this->assertStringContainsString('hq-story-cover', $index);
        $this->assertStringContainsString('hq-story-detail-cover', $detail);
        $this->assertStringContainsString('hamqadam-stories-v2.css', $detail);
        $this->assertStringContainsString('hamqadam-stories-v2.css', $index);
    }

    public function test_numeric_story_url_redirects_to_encoded_canonical_url(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/HomeController.php'));
        $this->assertStringContainsString("StoryPublicId::decode", $controller);
        $this->assertStringContainsString("->where('approved', 1)", $controller);
        $this->assertStringContainsString("301", $controller);
    }

    public function test_admin_sidebar_uses_script_after_shared_vendor_scripts(): void
    {
        $layout=file_get_contents(resource_path('views/admin/layouts/app.blade.php'));
        $script=$layout=str_replace('\\','', $layout);
        $this->assertStringContainsString('hamqadam-admin-active-sidebar.js', $script);
        $this->assertLessThan(strpos($layout,"hamqadam-admin-active-sidebar.js"),strpos($layout,"assets/js/aiz-core.js"));
    }
}
