<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiVerificationAttempt;
use App\Models\ProfileVerificationRequest;
use App\Models\User;
use App\Services\AiVerification\AiVerificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs AI identity verification out of band.
 *
 * Dispatch this with dispatchAfterResponse() from a registration flow. The
 * project runs QUEUE_CONNECTION=sync, where a plain dispatch() executes inline
 * and would add the model's multi-second CPU inference to the user's
 * registration request. afterResponse() runs it once the response has already
 * been sent, so registration stays fast on sync AND async queues alike.
 *
 * Set QUEUE_CONNECTION=database in production and run a worker to get real
 * retries and failure visibility.
 */
class RunAiVerification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        private readonly int $userId,
        private readonly string $source = AiVerificationAttempt::SOURCE_REGISTRATION_API,
        private readonly ?int $profileVerificationRequestId = null,
    ) {
        $this->onQueue('ai');
    }

    public function handle(AiVerificationService $service): void
    {
        $user = User::with('member')->find($this->userId);

        if (! $user) {
            Log::warning('ai_verification.job_user_missing', ['user_id' => $this->userId]);

            return;
        }

        $request = $this->profileVerificationRequestId
            ? ProfileVerificationRequest::with(['documents', 'user'])->find($this->profileVerificationRequestId)
            : null;

        // The service already swallows its own failures and records them; this
        // guard only exists so a queue worker never dies on an edge case.
        try {
            $service->verifyUser($user, $this->source, $request);
        } catch (Throwable $e) {
            Log::error('ai_verification.job_failed', [
                'user_id' => $this->userId,
                'source' => $this->source,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('ai_verification.job_permanently_failed', [
            'user_id' => $this->userId,
            'source' => $this->source,
            'message' => $e?->getMessage(),
        ]);
    }
}
