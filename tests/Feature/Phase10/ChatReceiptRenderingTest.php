<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class ChatReceiptRenderingTest extends TestCase
{
    public function test_every_outgoing_message_kind_renders_delivery_status(): void
    {
        $view = file_get_contents(resource_path('views/frontend/member/messages/messages_right_single.blade.php'));
        $this->assertSame(3, substr_count($view, "messages._receipt"));
        $this->assertSame(3, substr_count($view, 'class="hm-message-meta"'));
        $this->assertStringContainsString('chat-delete-message', $view);
        $partial = file_get_contents(resource_path('views/frontend/member/messages/_receipt.blade.php'));
        $this->assertStringContainsString('delivered_at', $partial);
        $this->assertStringContainsString('read_at', $partial);
        $this->assertStringContainsString("data-status=", $partial);
    }
}
