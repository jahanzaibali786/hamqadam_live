<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiFeatureRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAiFeatureRequest implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $aiFeatureRequestId)
    {
        $this->onQueue('ai');
    }

    public function handle(): void
    {
        AiFeatureRequest::whereKey($this->aiFeatureRequestId)->update([
            'status' => 'completed',
        ]);
    }
}
