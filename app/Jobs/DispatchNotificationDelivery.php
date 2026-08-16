<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationDeliveryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchNotificationDelivery implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $userId,
        private readonly string $channel,
        private readonly array $payload,
        private readonly ?string $notificationId = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'channel' => $this->channel,
            'status' => 'queued',
            'payload' => $this->payload,
            'sent_at' => now(),
        ]);
    }
}
