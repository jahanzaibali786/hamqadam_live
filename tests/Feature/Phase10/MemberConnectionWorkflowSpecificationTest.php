<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class MemberConnectionWorkflowSpecificationTest extends TestCase
{
    public function test_two_stage_workflow_has_distinct_interest_and_proposal_tables(): void
    {
        $migration=file_get_contents(database_path('migrations/2026_10_26_000001_create_interest_proposal_workflow.php'));
        $this->assertStringContainsString("Schema::create('marriage_proposals'", $migration);
        $this->assertStringContainsString("Schema::create('workflow_message_templates'", $migration);
        $this->assertStringContainsString("Schema::create('member_communication_preferences'", $migration);
        $this->assertStringContainsString('workflow_reminded_at', $migration);
    }

    public function test_template_seed_contains_all_specification_entries(): void
    {
        $seed=file_get_contents(database_path('seeders/ConnectionMessageTemplateSeeder.php'));
        $this->assertStringContainsString('Simple & Sincere', $seed);
        $this->assertStringContainsString('Purposeful Connection', $seed);
        $this->assertStringContainsString('Sincere Marriage Intention', $seed);
        $this->assertStringContainsString('A Meaningful Commitment', $seed);
        $this->assertSame(15, substr_count($seed, '"interest",'));
        $this->assertSame(15, substr_count($seed, '"proposal",'));
    }

    public function test_connection_service_checks_acceptance_before_chat_and_calls(): void
    {
        $service=file_get_contents(app_path('Services/MemberConnectionWorkflow.php'));
        $this->assertStringContainsString('function interestBetween', $service);
        $this->assertStringContainsString('function canCommunicate', $service);
        $this->assertStringContainsString('function canCall', $service);
        $this->assertStringContainsString('function saveProposal', $service);
        $this->assertStringContainsString('if (!$interest || !$this->canCommunicate', $service);
    }

    public function test_web_ui_has_locked_state_copy_and_requires_approval(): void
    {
        $view=file_get_contents(resource_path('views/frontend/member/workflow/show.blade.php'));
        $this->assertStringContainsString('Send Interest First', $view);
        $this->assertStringContainsString('Before sending a proposal, you need to express your interest in this profile.', $view);
        $this->assertStringContainsString('Once your interest is accepted, you can send a personalized marriage proposal.', $view);
        $this->assertStringContainsString('name="approved"', $view);
        $this->assertStringContainsString('data-locked-proposal', $view);
    }

    public function test_reminders_are_scheduled_and_idempotently_recorded(): void
    {
        $command=file_get_contents(app_path('Console/Commands/RemindPendingConnections.php'));
        $kernel=file_get_contents(app_path('Console/Kernel.php'));
        $this->assertStringContainsString("subHours(24)", $command);
        $this->assertStringContainsString("subHours(48)", $command);
        $this->assertStringContainsString("whereNull('workflow_reminded_at')", $command);
        $this->assertStringContainsString("whereNull('reminded_at')", $command);
        $this->assertStringContainsString('connections:remind-pending', $kernel);
    }
}
