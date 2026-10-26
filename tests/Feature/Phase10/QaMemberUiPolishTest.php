<?php
namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaMemberUiPolishTest extends TestCase
{
    public function test_messaging_has_no_duplicate_endsection(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/messages/index.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/@endsection\\s*@endsection\\s*$/',$blade);
    }
    public function test_settings_are_real_tab_controls(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/reference/settings.blade.php'));
        $this->assertStringContainsString('role="tablist"',$blade);
        $this->assertStringContainsString('data-settings-tab',$blade);
        $this->assertStringContainsString('data-settings-panel',$blade);
        $this->assertStringContainsString('aria-selected',$blade);
    }
    public function test_notifications_and_gallery_use_existing_member_actions(): void
    {
        $notification=file_get_contents(resource_path('views/frontend/member/notifications.blade.php'));
        $gallery=file_get_contents(resource_path('views/frontend/member/gallery_image/index.blade.php'));
        $this->assertStringContainsString("route('notification_view'",$notification);
        $this->assertStringContainsString("route('gallery_image.destroy'",$gallery);
        $this->assertStringContainsString('hm-gallery-grid',$gallery);
    }
}
