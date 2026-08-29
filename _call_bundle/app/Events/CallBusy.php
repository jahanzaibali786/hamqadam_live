<?php

declare(strict_types=1);

namespace App\Events;

class CallBusy extends CallBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'call-busy';
    }

    protected function recipientIds(): array
    {
        return [(int) ($this->payload['call']['caller']['id'] ?? 0)];
    }
}
