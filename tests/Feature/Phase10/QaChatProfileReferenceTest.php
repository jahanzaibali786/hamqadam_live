<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaChatProfileReferenceTest extends TestCase
{
    public function test_chat_reference_preserves_existing_message_transport(): void
    {
        $view = file_get_contents(resource_path('views/frontend/member/messages/index.blade.php'));
        $conversation = file_get_contents(resource_path('views/frontend/member/messages/messages.blade.php'));

        $this->assertStringContainsString('data-chat-filter="unread"', $view);
        $this->assertStringContainsString('data-chat-filter="archived"', $view);
        $this->assertStringContainsString('id="hm-chat-member-photo"', $view);
        $this->assertStringContainsString('data-match-score=', $view);
        $this->assertStringContainsString('loadChats(this)', $view);
        $this->assertStringContainsString('chat_thread_id', $conversation);
        $this->assertStringContainsString('id="send-mesaage"', $conversation);
        $this->assertStringContainsString('startCall(\'audio\')', $conversation);
        $this->assertStringContainsString('startCall(\'video\')', $conversation);
    }

    public function test_profile_details_are_directly_visible_without_expand_button(): void
    {
        $view = file_get_contents(resource_path('views/frontend/member/public_profile/reference_detail.blade.php'));
        $this->assertStringNotContainsString('hm-detail-show-complete', $view);
        $this->assertStringContainsString('hm-public-visible-sections', $view);
        $this->assertStringContainsString('Partner Preferences', $view);
        $this->assertStringContainsString('Photo Gallery', $view);
        $this->assertStringContainsString('galleryAllowed', $view);
        $this->assertStringContainsString('show_profile_picture($user)', $view);
    }
}
