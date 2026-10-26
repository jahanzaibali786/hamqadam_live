<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class AcceptedConnectionChatRegressionTest extends TestCase
{
    public function test_accepted_legacy_proposals_are_supported_without_unblocking_pending_connections(): void
    {
        $service = file_get_contents(app_path('Services/MemberConnectionWorkflow.php'));
        $this->assertStringContainsString('function hasAcceptedConnection', $service);
        $this->assertStringContainsString("where('interaction_type', 'proposal')", $service);
        $this->assertStringContainsString('ProposalStatus::Accepted->value', $service);
        $this->assertStringContainsString("whereNotNull('responded_at')", $service);
        $this->assertStringContainsString("where('event', 'proposal_accepted')", $service);
        $this->assertStringContainsString('ChatThread::query()', $service);
        $this->assertStringContainsString('function communicationDenialReason', $service);
        $this->assertStringContainsString('if ($this->isBlocked($a, $b))', $service);
    }

    public function test_web_and_mobile_messages_share_same_consent_check(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/ChatController.php'));
        $api = file_get_contents(app_path('Services/Api/V1/Chat/ChatApiService.php'));
        $this->assertStringContainsString('communicationDenialReason(', $web);
        $this->assertStringContainsString('communicationDenialReason(', $api);
        $this->assertStringContainsString('chat_thread_id', $web);
        $this->assertStringContainsString('voice_reply', $web);
        $this->assertStringContainsString('chat_not_permitted', $api);
    }
}
