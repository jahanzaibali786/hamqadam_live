<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Api\V1\Matching\MatchRecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateCompatibilityMatches implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $userId, public readonly int $limit = 100)
    {
    }

    public function handle(MatchRecommendationService $matches): void
    {
        $user = User::with('member', 'partner_expectations')->find($this->userId);

        if ($user) {
            $matches->recalculateFor($user, $this->limit);
        }
    }
}

