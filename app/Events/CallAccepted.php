<?php

declare(strict_types=1);

namespace App\Events;

class CallAccepted extends CallBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'call-accepted';
    }

    protected function recipientIds(): array
    {
        return [
            (int) ($this->payload['call']['caller']['id'] ?? 0),
            (int) ($this->payload['call']['receiver']['id'] ?? 0),
        ];
    }
}
