<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\HelpChat;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The member's Help Center conversation as the app sees it.
 */
class HelpChatThreadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'status' => (string) $this->status,
            'is_closed' => $this->status === \App\Models\HelpChatThread::STATUS_CLOSED,
            // Ticket lock: the member let the 5-minute response window after the
            // admin's reply lapse. The app renders "Start New chat" instead of
            // the composer; sending on this thread is refused server-side too.
            'locked' => $this->locked_at !== null,
            'locked_at' => optional($this->locked_at)->toISOString(),
            // Seconds left for the member to answer before the ticket locks —
            // null when no clock is running (no admin reply yet, already
            // answered, or already locked). The app may show a countdown.
            'response_deadline' => $this->responseDeadlineIso(),
            'unread_count' => (int) $this->user_unread_count,
            'last_message_at' => optional($this->last_message_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }

    /**
     * ISO deadline while the member still has time to reply, null otherwise.
     */
    private function responseDeadlineIso(): ?string
    {
        if ($this->locked_at !== null || ! $this->admin_replied_at) {
            return null;
        }

        $adminRepliedAt = \Carbon\Carbon::parse($this->admin_replied_at);
        $memberRepliedAt = $this->member_replied_at ? \Carbon\Carbon::parse($this->member_replied_at) : null;

        // The member already answered after the admin's reply — no clock.
        if ($memberRepliedAt && $memberRepliedAt->gte($adminRepliedAt)) {
            return null;
        }

        return $adminRepliedAt
            ->copy()
            ->addMinutes(\App\Services\HelpChatService::MEMBER_RESPONSE_WINDOW_MINUTES)
            ->toISOString();
    }
}
