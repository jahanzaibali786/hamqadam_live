<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HelpChatThread;
use Carbon\Carbon;

/**
 * Explicitly labels response SLA as the time since the most recent unread
 * member message. It is not an estimate of when an agent will resolve the case.
 */
final class HelpChatSlaService
{
    public function thresholdMinutes(): int
    {
        return max(5, min(10080, (int) config('help_chat_sla.response_minutes', 60)));
    }

    public function isAwaitingAgent(HelpChatThread $thread): bool
    {
        return $thread->status === HelpChatThread::STATUS_OPEN
            && (int) $thread->admin_unread_count > 0
            && $thread->last_message_at !== null;
    }

    public function isOverdue(HelpChatThread $thread, ?Carbon $at = null): bool
    {
        if (! $this->isAwaitingAgent($thread)) return false;
        $at ??= now();
        return Carbon::parse($thread->last_message_at)
            ->addMinutes($this->thresholdMinutes())->lte($at);
    }

    public function firstResponseDeadline(HelpChatThread $thread): ?string
    {
        if (! $this->isAwaitingAgent($thread)) return null;
        return Carbon::parse($thread->last_message_at)
            ->addMinutes($this->thresholdMinutes())->toISOString();
    }
}
