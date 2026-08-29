<?php

declare(strict_types=1);

namespace App\Events;

class CallMissed extends CallBroadcastEvent
{
    public function broadcastAs(): string
    {
        return 'call-missed';
    }

    protected function recipientIds(): array
    {
        return [
            (int) ($this->payload['call']['caller']['id'] ?? 0),
            (int) ($this->payload['call']['receiver']['id'] ?? 0),
        ];
    }
}
