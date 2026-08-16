<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Family;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\ExpressInterest;
use App\Models\FamilyApprovalRequest;
use App\Models\FamilyConversation;
use App\Models\FamilyConversationMessage;
use App\Models\FamilyGuardianLink;
use App\Models\FamilyPrivateNote;
use App\Models\ProfileMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FamilyService
{
    public function guardians(User $user)
    {
        return FamilyGuardianLink::with(['profile', 'guardian'])
            ->where('profile_user_id', $user->id)
            ->latest()
            ->paginate(20);
    }

    public function managedProfiles(User $guardian)
    {
        return FamilyGuardianLink::with(['profile', 'guardian'])
            ->where('guardian_user_id', $guardian->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate(20);
    }

    public function storeGuardian(User $profile, array $data): FamilyGuardianLink
    {
        if ((int) $data['guardian_user_id'] === (int) $profile->id) {
            throw new ApiException('You cannot add yourself as guardian.', 422, ApiErrorCode::ValidationFailed->value);
        }

        return FamilyGuardianLink::updateOrCreate([
            'profile_user_id' => $profile->id,
            'guardian_user_id' => $data['guardian_user_id'],
        ], [
            'relationship' => $data['relationship'] ?? null,
            'guardian_role' => $data['guardian_role'] ?? $data['relationship'] ?? null,
            'is_wali' => (bool) ($data['is_wali'] ?? false),
            'permissions' => $data['permissions'] ?? ['approve_matches', 'view_notes'],
            'digest_frequency' => $data['digest_frequency'] ?? 'weekly',
            'status' => 'pending',
            'approved_at' => null,
        ])->load(['profile', 'guardian']);
    }

    public function approveGuardian(User $guardian, int $linkId): FamilyGuardianLink
    {
        $link = FamilyGuardianLink::where('guardian_user_id', $guardian->id)->findOrFail($linkId);
        $link->forceFill(['status' => 'approved', 'approved_at' => now()])->save();

        return $link->fresh(['profile', 'guardian']);
    }

    public function revokeGuardian(User $user, int $linkId): void
    {
        FamilyGuardianLink::where(function ($query) use ($user) {
            $query->where('profile_user_id', $user->id)->orWhere('guardian_user_id', $user->id);
        })->whereKey($linkId)->delete();
    }

    public function approvalRequests(User $user)
    {
        return FamilyApprovalRequest::with(['profile', 'guardian'])
            ->where(function ($query) use ($user) {
                $query->where('profile_user_id', $user->id)->orWhere('guardian_user_id', $user->id);
            })
            ->latest()
            ->paginate(20);
    }

    public function requestApproval(User $profile, array $data): FamilyApprovalRequest
    {
        $this->ensureApprovedGuardian($profile->id, (int) $data['guardian_user_id']);

        return FamilyApprovalRequest::create([
            'profile_user_id' => $profile->id,
            'guardian_user_id' => $data['guardian_user_id'],
            'request_type' => $data['request_type'],
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
        ])->load(['profile', 'guardian']);
    }

    public function decideApproval(User $guardian, int $requestId, string $status, ?string $note): FamilyApprovalRequest
    {
        $approval = FamilyApprovalRequest::where('guardian_user_id', $guardian->id)->findOrFail($requestId);
        $approval->forceFill([
            'status' => $status,
            'decision_note' => $note,
            'decided_at' => now(),
        ])->save();

        return $approval->fresh(['profile', 'guardian']);
    }

    public function notes(User $user, int $profileUserId)
    {
        if ($user->id !== $profileUserId) {
            $this->ensureApprovedGuardian($profileUserId, $user->id);
        }

        return FamilyPrivateNote::with(['profile', 'author'])
            ->where('profile_user_id', $profileUserId)
            ->latest()
            ->paginate(20);
    }

    public function storeNote(User $author, array $data): FamilyPrivateNote
    {
        if ((int) $data['profile_user_id'] !== (int) $author->id) {
            $this->ensureApprovedGuardian((int) $data['profile_user_id'], $author->id);
        }

        return FamilyPrivateNote::create([
            'profile_user_id' => $data['profile_user_id'],
            'author_user_id' => $author->id,
            'note' => $data['note'],
        ])->load(['profile', 'author']);
    }

    public function dashboard(User $guardian, ?int $profileUserId = null): array
    {
        $managedIds = FamilyGuardianLink::where('guardian_user_id', $guardian->id)
            ->where('status', 'approved')
            ->when($profileUserId, fn ($query) => $query->where('profile_user_id', $profileUserId))
            ->pluck('profile_user_id');

        if ($managedIds->isEmpty()) {
            throw new ApiException('No approved managed profile found.', 403, ApiErrorCode::Forbidden->value);
        }

        return [
            'managed_profile_ids' => $managedIds->values(),
            'pending_approvals' => FamilyApprovalRequest::whereIn('profile_user_id', $managedIds)
                ->where('guardian_user_id', $guardian->id)
                ->where('status', 'pending')
                ->count(),
            'active_proposals' => ExpressInterest::with(['sender:id,first_name,last_name', 'recipient:id,first_name,last_name'])
                ->where(function ($query) use ($managedIds) {
                    $query->whereIn('interested_by', $managedIds)->orWhereIn('user_id', $managedIds);
                })
                ->latest()
                ->limit(10)
                ->get(),
            'recommended_matches' => ProfileMatch::with('matchedUser:id,first_name,last_name')
                ->whereIn('user_id', $managedIds)
                ->latest('match_percentage')
                ->limit(10)
                ->get(),
        ];
    }

    public function updateGuardian(User $profile, int $linkId, array $data): FamilyGuardianLink
    {
        $link = FamilyGuardianLink::where('profile_user_id', $profile->id)->findOrFail($linkId);
        $link->fill($data)->save();

        return $link->fresh(['profile', 'guardian']);
    }

    public function setWaliMode(User $profile, bool $enabled): array
    {
        $member = $profile->member()->firstOrFail();
        $member->forceFill(['wali_mode_enabled' => $enabled])->save();

        return [
            'wali_mode_enabled' => $enabled,
            'message' => $enabled
                ? 'Wali mode enabled. Guardian participation is required for family workflows.'
                : 'Wali mode disabled.',
        ];
    }

    public function conversations(User $actor)
    {
        $profileIds = $this->accessibleProfileIds($actor);

        return FamilyConversation::with(['firstProfile:id,first_name,last_name', 'secondProfile:id,first_name,last_name', 'messages.sender:id,first_name,last_name'])
            ->where(function ($query) use ($profileIds) {
                $query->whereIn('first_profile_user_id', $profileIds)
                    ->orWhereIn('second_profile_user_id', $profileIds);
            })
            ->latest()
            ->paginate(20);
    }

    public function startConversation(User $actor, array $data): FamilyConversation
    {
        $proposal = ! empty($data['proposal_id'])
            ? ExpressInterest::findOrFail((int) $data['proposal_id'])
            : null;

        $firstProfileId = $proposal ? (int) $proposal->interested_by : $actor->id;
        $secondProfileId = $proposal ? (int) $proposal->user_id : (int) $data['profile_user_id'];

        $this->ensureFamilyConversationAccess($actor, $firstProfileId, $secondProfileId);

        return DB::transaction(function () use ($actor, $data, $proposal, $firstProfileId, $secondProfileId) {
            $conversation = FamilyConversation::firstOrCreate([
                'proposal_id' => $proposal?->id,
                'first_profile_user_id' => min($firstProfileId, $secondProfileId),
                'second_profile_user_id' => max($firstProfileId, $secondProfileId),
            ], [
                'created_by' => $actor->id,
                'status' => 'active',
            ]);

            FamilyConversationMessage::create([
                'family_conversation_id' => $conversation->id,
                'sender_user_id' => $actor->id,
                'message' => $data['message'],
            ]);

            return $conversation->fresh(['firstProfile', 'secondProfile', 'messages.sender']);
        });
    }

    public function messages(User $actor, int $conversationId)
    {
        $conversation = FamilyConversation::findOrFail($conversationId);
        $this->ensureFamilyConversationAccess($actor, (int) $conversation->first_profile_user_id, (int) $conversation->second_profile_user_id);

        return $conversation->messages()->with('sender:id,first_name,last_name')->latest()->paginate(30);
    }

    public function sendMessage(User $actor, int $conversationId, array $data): FamilyConversationMessage
    {
        $conversation = FamilyConversation::findOrFail($conversationId);
        $this->ensureFamilyConversationAccess($actor, (int) $conversation->first_profile_user_id, (int) $conversation->second_profile_user_id);

        return FamilyConversationMessage::create([
            'family_conversation_id' => $conversation->id,
            'sender_user_id' => $actor->id,
            'message' => $data['message'],
            'attachments' => $data['attachments'] ?? null,
        ])->load('sender:id,first_name,last_name');
    }

    public function digestPreview(User $guardian): array
    {
        $managedIds = FamilyGuardianLink::where('guardian_user_id', $guardian->id)
            ->where('status', 'approved')
            ->pluck('profile_user_id');

        return [
            'managed_profiles' => $managedIds->count(),
            'new_proposals_this_week' => ExpressInterest::where(function ($query) use ($managedIds) {
                $query->whereIn('interested_by', $managedIds)->orWhereIn('user_id', $managedIds);
            })->where('created_at', '>=', now()->subWeek())->count(),
            'pending_family_approvals' => FamilyApprovalRequest::where('guardian_user_id', $guardian->id)
                ->where('status', 'pending')
                ->count(),
        ];
    }

    private function ensureApprovedGuardian(int $profileUserId, int $guardianUserId): void
    {
        $exists = FamilyGuardianLink::where('profile_user_id', $profileUserId)
            ->where('guardian_user_id', $guardianUserId)
            ->where('status', 'approved')
            ->exists();

        if (! $exists) {
            throw new ApiException('Approved guardian relationship is required.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function accessibleProfileIds(User $actor): array
    {
        $managedIds = FamilyGuardianLink::where('guardian_user_id', $actor->id)
            ->where('status', 'approved')
            ->pluck('profile_user_id')
            ->all();

        return array_values(array_unique(array_merge([$actor->id], $managedIds)));
    }

    private function ensureFamilyConversationAccess(User $actor, int $firstProfileId, int $secondProfileId): void
    {
        $accessible = $this->accessibleProfileIds($actor);

        if (in_array($firstProfileId, $accessible, true) || in_array($secondProfileId, $accessible, true)) {
            return;
        }

        throw new ApiException('You do not have access to this family conversation.', 403, ApiErrorCode::Forbidden->value);
    }
}
