<?php

declare(strict_types=1);

namespace App\Events;

class CallIncoming extends CallBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'call-incoming';
    }

    protected function recipientIds(): array
    {
        return [(int) ($this->payload['call']['receiver']['id'] ?? 0)];
    }
}
