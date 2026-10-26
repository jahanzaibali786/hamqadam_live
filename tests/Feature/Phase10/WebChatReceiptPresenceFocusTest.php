<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class WebChatReceiptPresenceFocusTest extends TestCase
{
    public function test_sent_delivered_read_ticks_and_focus_styles_are_available(): void
    {
        $receipt=file_get_contents(resource_path('views/frontend/member/messages/_receipt.blade.php'));
        $css=file_get_contents(public_path('assets/css/hamqadam-chat-receipts-focus-20261026.css'));
        foreach (['delivered_at','read_at','data-message-id','data-status','Seen','Delivered'] as $term) {
            $this->assertStringContainsString($term,$receipt);
        }
        $this->assertStringContainsString('#a84469',$css);
        $this->assertStringContainsString('box-shadow:none!important',$css);
        $this->assertStringContainsString('.hm-member-app',$css);
    }

    public function test_web_receipts_are_authenticated_and_message_updates_are_scoped(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/ChatController.php'));
        $routes=file_get_contents(base_path('routes/web.php'));
        $script=file_get_contents(public_path('assets/js/hamqadam-chat-receipts-20261026.js'));
        $this->assertStringContainsString('function delivered_ping',$controller);
        $this->assertStringContainsString('function presence_ping',$controller);
        $this->assertStringContainsString('$this->ensureParticipant($thread)',$controller);
        $this->assertStringContainsString('chat.web.delivered',$routes);
        $this->assertStringContainsString('chat.web.presence',$routes);
        $this->assertStringContainsString("'.message-delivered'",$script);
        $this->assertStringContainsString("'.message-read'",$script);
        $this->assertStringContainsString("'.typing-indicator'",$script);
    }
}
