<?php

declare(strict_types=1);

namespace App\Events;

class CallCancelled extends CallBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'call-cancelled';
    }

    protected function recipientIds(): array
    {
        return [(int) ($this->payload['call']['receiver']['id'] ?? 0)];
    }
}
