<?php

declare(strict_types=1);

namespace Tests\Feature\Phase7;

use App\Models\User;
use App\Services\Api\V1\Notification\NotificationDeepLinkService;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class CompletionGuardTest extends TestCase
{
    public function test_notification_deep_links_reject_external_urls(): void
    {
        $service = app(NotificationDeepLinkService::class);
        $user = new User();
        foreach (['https://evil.invalid', '//evil.invalid', '/\\evil.invalid', 'javascript:alert(1)'] as $url) {
            $this->assertSame('/dashboard', $service->resolve($user, ['route'=>$url]));
        }
        $this->assertSame('/member/referrals', $service->resolve($user, ['route'=>'/member/referrals']));
    }

    public function test_admin_dashboard_blade_compiles(): void
    {
        $view = file_get_contents(resource_path('views/admin/client_scope/dashboard.blade.php'));
        $this->assertNotFalse($view);
        $compiled = Blade::compileString($view);
        $this->assertStringContainsString('Linked-user journey', $compiled);
    }
}
