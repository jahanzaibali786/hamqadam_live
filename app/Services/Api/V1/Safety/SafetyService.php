<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Safety;

use App\Enums\ApiErrorCode;
use App\Enums\ModerationCaseStatus;
use App\Enums\SafetyActionType;
use App\Exceptions\ApiException;
use App\Models\ModerationCase;
use App\Models\ReportedUser;
use App\Models\SafetyAction;
use App\Models\SuspiciousActivityLog;
use App\Models\User;

class SafetyService
{
    public function report(User $actor, array $data): ModerationCase
    {
        $this->ensureNotSelf($actor, (int) $data['user_id']);

        ReportedUser::firstOrCreate([
            'user_id' => $data['user_id'],
            'reported_by' => $actor->id,
        ], [
            'reason' => $data['reason'],
        ]);

        SafetyAction::create([
            'actor_user_id' => $actor->id,
            'target_user_id' => $data['user_id'],
            'action_type' => SafetyActionType::Report,
            'reason' => $data['reason'],
            'metadata' => $data['evidence'] ?? [],
        ]);

        SuspiciousActivityLog::create([
            'user_id' => $data['user_id'],
            'activity_type' => 'user_reported',
            'risk_level' => $data['severity'] ?? 'medium',
            'risk_score' => $this->riskScore($data['severity'] ?? 'medium'),
            'signals' => ['reason' => $data['reason']],
        ]);

        return ModerationCase::create([
            'reported_user_id' => $data['user_id'],
            'reporter_user_id' => $actor->id,
            'case_type' => 'user_report',
            'status' => ModerationCaseStatus::Open,
            'severity' => $data['severity'] ?? 'medium',
            'reason' => $data['reason'],
            'evidence' => $data['evidence'] ?? [],
        ])->load(['reportedUser', 'reporter']);
    }

    public function action(User $actor, array $data, SafetyActionType $type): SafetyAction
    {
        $this->ensureNotSelf($actor, (int) $data['user_id']);

        $action = SafetyAction::create([
            'actor_user_id' => $actor->id,
            'target_user_id' => $data['user_id'],
            'action_type' => $type,
            'reason' => $data['reason'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        if ($type === SafetyActionType::Block || $type === SafetyActionType::Restrict) {
            User::whereKey($data['user_id'])->update(['blocked' => $type === SafetyActionType::Restrict ? 1 : 0]);
        }

        return $action;
    }

    public function queue(User $moderator, array $filters)
    {
        $this->ensureModerator($moderator);

        $query = ModerationCase::with(['reportedUser', 'reporter'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function resolve(User $moderator, int $caseId, array $data): ModerationCase
    {
        $this->ensureModerator($moderator);

        $case = ModerationCase::findOrFail($caseId);
        $case->forceFill([
            'status' => $data['status'],
            'assigned_to' => $moderator->id,
            'resolution_note' => $data['resolution_note'] ?? null,
            'resolved_at' => now(),
        ])->save();

        return $case->fresh(['reportedUser', 'reporter']);
    }

    private function ensureModerator(User $user): void
    {
        if ($user->user_type === 'member') {
            throw new ApiException('Only moderators can access this resource.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function ensureNotSelf(User $actor, int $targetUserId): void
    {
        if ((int) $actor->id === $targetUserId) {
            throw new ApiException('You cannot perform this action on yourself.', 422, ApiErrorCode::ValidationFailed->value);
        }
    }

    private function riskScore(string $severity): float
    {
        return match ($severity) {
            'critical' => 95,
            'high' => 80,
            'low' => 25,
            default => 55,
        };
    }
}
