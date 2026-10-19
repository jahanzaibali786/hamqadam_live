<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class InspectOperationalReadiness extends Command
{
    protected $signature = 'hamqadam:readiness';
    protected $description = 'Read-only operational checks for email, notification, support and payment prerequisites';

    public function handle(): int
    {
        $checks = [
            'Official mail sender' => str_ends_with(strtolower((string) config('mail.from.address')), '@hamqadam.com'),
            'Delivering mailer configured' => ! in_array(config('mail.default'), ['log','array'], true),
            'Notification audit table' => Schema::hasTable('notification_delivery_logs'),
            'Help chat table' => Schema::hasTable('help_chat_threads'),
            'Support agent assignment' => Schema::hasTable('help_chat_threads') && Schema::hasColumn('help_chat_threads', 'assigned_agent_id'),
            'Analytics events table' => Schema::hasTable('analytics_events'),
            'Refund attempt ledger' => Schema::hasTable('payment_refund_attempts'),
        ];
        $rows = [];
        foreach ($checks as $label => $passed) $rows[] = [$label, $passed ? 'READY' : 'ACTION REQUIRED'];
        $this->table(['Prerequisite', 'Assessment'], $rows);
        $this->warn('Readiness verifies configuration/schema presence only, not SMTP delivery, gateway processing, or mobile app behavior.');
        return in_array(false, array_values($checks), true) ? self::FAILURE : self::SUCCESS;
    }
}
