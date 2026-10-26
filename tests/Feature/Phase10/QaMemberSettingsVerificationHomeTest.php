<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class QaMemberSettingsVerificationHomeTest extends TestCase
{
    public function test_home_is_separate_from_dashboard(): void
    {
        $routes=file_get_contents(base_path('routes/member_reference.php'));
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
        $this->assertStringContainsString("->name('home')",$routes);
        $this->assertStringContainsString("'route' => 'member.reference.home'",$layout);
        $this->assertStringContainsString('Choose forever.',file_get_contents(resource_path('views/frontend/member/reference/home.blade.php')));
    }
    public function test_settings_keeps_real_server_side_forms(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/reference/settings.blade.php'));
        $this->assertStringContainsString("route('member.reference.settings.privacy')",$view);
        $this->assertStringContainsString("route('member.reference.settings.notifications')",$view);
        $this->assertStringContainsString('@csrf',$view);
        $this->assertStringContainsString('Profile Preview',$view);
        $this->assertStringContainsString('Blocked Users',$view);
    }
    public function test_verification_uses_existing_submission_and_history(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/member_verifiction_form.blade.php'));
        $this->assertStringContainsString("route('member.verification_info.store')",$view);
        $this->assertStringContainsString('$verificationAttempts',$view);
        $this->assertStringContainsString('CNIC Verification',$view);
        $this->assertStringContainsString('Selfie Verification',$view);
        $this->assertStringContainsString('@csrf',$view);
    }
}
