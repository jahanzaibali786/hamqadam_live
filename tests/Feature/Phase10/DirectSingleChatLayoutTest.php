<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class DirectSingleChatLayoutTest extends TestCase
{
    public function test_direct_chat_navigation_uses_full_messaging_shell_and_ajax_keeps_fragment(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/ChatController.php'));
        $shell=file_get_contents(resource_path('views/frontend/member/messages/index.blade.php'));
        $layout=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));

        $this->assertStringContainsString('public function chat_view(Request $request, $id)', $controller);
        $this->assertStringContainsString('if (! $request->ajax())', $controller);
        $this->assertStringContainsString("->route('all.messages', ['thread' => $chat_thread->id])", $controller);
        $this->assertStringContainsString("view('frontend.member.messages.messages'", $controller);
        $this->assertStringContainsString("new URLSearchParams(location.search).get('thread')", $shell);
        $this->assertStringContainsString("if (initial && typeof loadChats === 'function') initial.click();", $shell);
        $this->assertStringContainsString("@extends('frontend.layouts.member_panel')", $shell);
        $this->assertStringContainsString('hamqadam-chat-reference-20261023.css', $layout);
    }
}
