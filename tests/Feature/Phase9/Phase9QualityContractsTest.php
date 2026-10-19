<?php

declare(strict_types=1);

namespace Tests\Feature\Phase9;

use App\Models\HelpChatThread;
use App\Services\HelpChatSlaService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class Phase9QualityContractsTest extends TestCase
{
    public function test_help_chat_sla_only_flags_open_unanswered_member_messages(): void
    {
        config()->set('help_chat_sla.response_minutes', 60);
        $thread = new HelpChatThread();
        $thread->status = 'open';
        $thread->admin_unread_count = 1;
        $thread->last_message_at = now()->subMinutes(70)->toDateTimeString();
        $sla = new HelpChatSlaService();
        $this->assertTrue($sla->isOverdue($thread));
        $thread->admin_unread_count = 0;
        $this->assertFalse($sla->isOverdue($thread));
    }

    public function test_admin_help_chat_remains_authenticated(): void
    {
        $route = Route::getRoutes()->getByName('admin.help-chat.index');
        $this->assertNotNull($route);
        $this->assertContains('admin', $route->gatherMiddleware());
    }
}
