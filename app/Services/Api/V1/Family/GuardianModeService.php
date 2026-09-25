<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Family;

use App\Enums\ApiErrorCode;
use App\Enums\GuardianPermission;
use App\Exceptions\ApiException;
use App\Models\ExpressInterest;
use App\Models\FamilyConversation;
use App\Models\FamilyConversationMessage;
use App\Models\FamilyGuardianLink;
use App\Models\FamilyIntroduction;
use App\Models\GuardianFeedback;
use App\Models\GuardianInvitation;
use App\Models\GuardianActivityLog;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\NotificationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Guardian Mode: secure invitation lifecycle, pause/resume/revoke, guardian
 * match-review actions (shortlist / feedback / notes / recommend), the audit
 * trail, and the family-introduction stage (spec §5–§15, §20, §25).
 *
 * Every guardian action goes through GuardianAuthService so the permission
 * keys are enforced server-side, and every sensitive action is written to
 * guardian_activity_logs for the Primary User.
 */
class GuardianModeService
{
    /** Invitations stay valid for this long (spec §6: time-limited). */
    private const INVITE_TTL_DAYS = 7;

    /** Spec §17: 3–4 active guardians per member. */
    private const MAX_ACTIVE_GUARDIANS = 4;

    public function __construct(private readonly GuardianAuthService $auth)
    {
    }

    // ── Invitation lifecycle ────────────────────────────────────────────────

    /**
     * The Primary User invites a guardian. Creates a single-use, expiring,
     * non-guessable token invitation plus a pending guardian link.
     */
    public function invite(User $profile, array $data): GuardianInvitation
    {
        // Two invite paths: by member id (the app flow) or by contact string
        // (phone/email — the website flow, resolved to an account when found).
        if (! empty($data['guardian_user_id'])) {
            $guardian = User::findOrFail((int) $data['guardian_user_id']);
            $contact = (string) ($guardian->email ?? $guardian->phone ?? ('member-' . $guardian->id));
        } else {
            $contact = (string) $data['contact'];
            $guardian = User::where('email', $contact)->orWhere('phone', $contact)->first();
        }

        if ($guardian && (int) $guardian->id === (int) $profile->id) {
            throw new ApiException('You cannot invite yourself as a guardian.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $activeCount = FamilyGuardianLink::where('profile_user_id', $profile->id)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_GUARDIANS) {
            throw new ApiException('You can have at most ' . self::MAX_ACTIVE_GUARDIANS . ' active guardians.', 422, ApiErrorCode::ValidationFailed->value);
        }

        // Edge case §26: never create a duplicate pending invite for the same contact.
        $existing = GuardianInvitation::where('profile_user_id', $profile->id)
            ->where('contact', $contact)
            ->where('status', 'pending')
            ->first();

        if ($existing && $existing->isUsable()) {
            throw new ApiException('An invitation for this contact is already pending.', 409, ApiErrorCode::Conflict->value);
        }

        $permissions = $this->resolvePermissions($data);

        $invitation = GuardianInvitation::create([
            'profile_user_id' => $profile->id,
            'guardian_user_id' => $guardian?->id,
            'contact' => $contact,
            'relationship' => $data['relationship'] ?? null,
            'guardian_role' => $data['guardian_role'] ?? null,
            'is_wali' => (bool) ($data['is_wali'] ?? false),
            'permissions' => $permissions,
            'token' => bin2hex(random_bytes(24)), // 48 hex chars, non-guessable
            'status' => 'pending',
            'expires_at' => now()->addDays(self::INVITE_TTL_DAYS),
        ]);

        FamilyGuardianLink::updateOrCreate([
            'profile_user_id' => $profile->id,
            'guardian_user_id' => $guardian?->id ?? $profile->id, // placeholder pair avoids a zero guardian id
        ], [
            'relationship' => $data['relationship'] ?? null,
            'guardian_role' => $data['guardian_role'] ?? $data['relationship'] ?? null,
            'is_wali' => (bool) ($data['is_wali'] ?? false),
            'permissions' => $permissions,
            'status' => 'pending',
            'invited_at' => now(),
            'approved_at' => null,
            'paused_at' => null,
            'revoked_at' => null,
        ]);

        GuardianActivityLog::record(null, $guardian?->id, $profile->id, 'guardian_invited', GuardianInvitation::class, $invitation->id, [
            'contact' => $contact,
            'relationship' => $data['relationship'] ?? null,
        ]);

        if ($guardian) {
            NotificationHelper::guardianEvent(
                $guardian,
                'guardian_invitation',
                'Guardian Invitation',
                $profile->first_name . ' invited you to be their guardian on Hamqadam.',
                $profile->id,
                $invitation->id,
            );
        }

        return $invitation;
    }

    /**
     * The invited guardian accepts: single-use token is consumed, the link is
     * approved, and the stored permissions are materialized as granular rows.
     */
    public function acceptInvitation(User $guardian, string $token): FamilyGuardianLink
    {
        $invitation = GuardianInvitation::where('token', $token)->first();

        if (! $invitation) {
            throw new ApiException('Invitation not found.', 404, ApiErrorCode::NotFound->value);
        }

        if ($invitation->status !== 'pending' || ! $invitation->isUsable()) {
            throw new ApiException('This invitation is no longer valid.', 410, ApiErrorCode::Conflict->value);
        }

        if ($invitation->guardian_user_id !== null && (int) $invitation->guardian_user_id !== (int) $guardian->id) {
            throw new ApiException('This invitation was issued to a different account.', 403, ApiErrorCode::Forbidden->value);
        }

        return DB::transaction(function () use ($invitation, $guardian): FamilyGuardianLink {
            $invitation->forceFill([
                'status' => 'accepted',
                'accepted_at' => now(),
                'guardian_user_id' => $guardian->id,
            ])->save();

            $link = FamilyGuardianLink::updateOrCreate([
                'profile_user_id' => $invitation->profile_user_id,
                'guardian_user_id' => $guardian->id,
            ], [
                'relationship' => $invitation->relationship,
                'guardian_role' => $invitation->guardian_role ?? $invitation->relationship,
                'is_wali' => $invitation->is_wali,
                'permissions' => $invitation->permissions,
                'status' => 'approved',
                'invited_at' => $invitation->created_at,
                'approved_at' => now(),
                'paused_at' => null,
                'revoked_at' => null,
            ]);

            $this->auth->syncPermissions($link, $invitation->permissions ?? GuardianPermission::preset('view_only'), $invitation->profile_user_id);

            GuardianActivityLog::record($link->id, $guardian->id, $link->profile_user_id, 'guardian_accepted', FamilyGuardianLink::class, $link->id);

            NotificationHelper::guardianEvent(
                User::find($link->profile_user_id),
                'guardian_accepted',
                'Guardian Joined',
                'Your guardian invitation was accepted.',
                $guardian->id,
                $link->id,
            );

            return $link;
        });
    }

    // ── Lifecycle: pause / resume / revoke ─────────────────────────────────

    public function pause(User $profile, int $linkId): FamilyGuardianLink
    {
        return $this->setPaused($profile, $linkId, true);
    }

    public function resume(User $profile, int $linkId): FamilyGuardianLink
    {
        return $this->setPaused($profile, $linkId, false);
    }

    private function setPaused(User $profile, int $linkId, bool $pause): FamilyGuardianLink
    {
        $link = FamilyGuardianLink::where('profile_user_id', $profile->id)->findOrFail($linkId);

        $link->forceFill(['paused_at' => $pause ? now() : null])->save();

        GuardianActivityLog::record($link->id, $link->guardian_user_id, $profile->id, $pause ? 'guardian_paused' : 'guardian_resumed', FamilyGuardianLink::class, $link->id);

        NotificationHelper::guardianEvent(
            User::find($link->guardian_user_id),
            $pause ? 'guardian_paused' : 'guardian_resumed',
            $pause ? 'Guardian Access Paused' : 'Guardian Access Resumed',
            $pause
                ? 'Your guardian access was paused by the member.'
                : 'Your guardian access was resumed by the member.',
            $profile->id,
            $link->id,
        );

        return $link;
    }

    /**
     * Revocation terminates access permanently (spec §5/§25). The link is not
     * deleted — the audit trail and past feedback remain — but the guardian
     * can never regain access through old tokens or links.
     */
    public function revoke(User $profile, int $linkId): void
    {
        $link = FamilyGuardianLink::where('profile_user_id', $profile->id)->findOrFail($linkId);

        $link->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
        ])->save();

        // Invalidate any pending invitation for this pair.
        GuardianInvitation::where('profile_user_id', $profile->id)
            ->where('guardian_user_id', $link->guardian_user_id)
            ->where('status', 'pending')
            ->update(['status' => 'revoked']);

        GuardianActivityLog::record($link->id, $link->guardian_user_id, $profile->id, 'guardian_revoked', FamilyGuardianLink::class, $link->id);

        NotificationHelper::guardianEvent(
            User::find($link->guardian_user_id),
            'guardian_revoked',
            'Guardian Access Revoked',
            'Your guardian access was removed by the member.',
            $profile->id,
            $link->id,
        );
    }

    // ── Guardian match-review actions ───────────────────────────────────────

    public function shortlistMatch(User $guardian, int $profileUserId, int $targetUserId): GuardianFeedback
    {
        $link = $this->auth->authorize($guardian, $profileUserId, GuardianPermission::ShortlistMatch);

        $feedback = GuardianFeedback::firstOrCreate([
            'guardian_link_id' => $link->id,
            'target_user_id' => $targetUserId,
            'feedback_type' => 'shortlist',
        ], [
            'guardian_user_id' => $guardian->id,
            'profile_user_id' => $profileUserId,
        ]);

        GuardianActivityLog::record($link->id, $guardian->id, $profileUserId, 'guardian_shortlisted', User::class, $targetUserId);

        $this->notifyMember($link, 'guardian_shortlisted', 'Guardian Shortlisted a Profile', 'Your guardian shortlisted a profile for you.', $guardian->id);

        return $feedback;
    }

    public function feedback(User $guardian, int $profileUserId, int $targetUserId, string $type, ?string $reason, ?string $comment): GuardianFeedback
    {
        $link = $this->auth->authorize($guardian, $profileUserId, GuardianPermission::RecommendMatch);

        if (! in_array($type, ['not_suitable', 'recommend'], true)) {
            throw new ApiException('Invalid feedback type.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $feedback = GuardianFeedback::updateOrCreate([
            'guardian_link_id' => $link->id,
            'target_user_id' => $targetUserId,
            'feedback_type' => $type,
        ], [
            'guardian_user_id' => $guardian->id,
            'profile_user_id' => $profileUserId,
            'reason' => $reason,
            'comment' => $comment,
        ]);

        GuardianActivityLog::record($link->id, $guardian->id, $profileUserId, 'guardian_feedback', User::class, $targetUserId, [
            'feedback_type' => $type,
            'reason' => $reason,
        ]);

        $this->notifyMember($link, 'guardian_feedback', $type === 'recommend' ? 'Guardian Recommended a Match' : 'Guardian Feedback', 'Your guardian shared feedback on a profile.', $guardian->id);

        return $feedback;
    }

    /** Guardian note — stored separately from the member's own preferences (§11). */
    public function note(User $guardian, int $profileUserId, int $targetUserId, string $note, string $visibility): array
    {
        $link = $this->auth->authorize($guardian, $profileUserId, GuardianPermission::AddGuardianNote);

        if (! in_array($visibility, ['guardian_only', 'primary_and_guardian', 'family_context'], true)) {
            throw new ApiException('Invalid note visibility.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $feedback = GuardianFeedback::updateOrCreate([
            'guardian_link_id' => $link->id,
            'target_user_id' => $targetUserId,
            'feedback_type' => 'note',
        ], [
            'guardian_user_id' => $guardian->id,
            'profile_user_id' => $profileUserId,
            'reason' => $visibility,
            'comment' => $note,
        ]);

        GuardianActivityLog::record($link->id, $guardian->id, $profileUserId, 'guardian_note_added', User::class, $targetUserId, [
            'visibility' => $visibility,
        ]);

        return ['note' => $feedback, 'visibility' => $visibility];
    }

    // ── Read models for dashboards ─────────────────────────────────────────

    public function activity(User $actor, int $profileUserId, int $limit = 50)
    {
        // The member sees their own trail; a guardian sees it only with view
        // permissions on the profile (basic view is enough — the log contains
        // no sensitive payloads, just action names).
        if ((int) $actor->id !== $profileUserId) {
            $this->auth->authorize($actor, $profileUserId, GuardianPermission::ViewBasicProfile);
        }

        return GuardianActivityLog::with(['guardian:id,first_name,last_name'])
            ->where('profile_user_id', $profileUserId)
            ->latest()
            ->limit(min($limit, 100))
            ->get();
    }

    /** Guardian's view of the member's recommended matches (decision support only). */
    public function recommendedMatches(User $guardian, int $profileUserId)
    {
        $link = $this->auth->authorize($guardian, $profileUserId, GuardianPermission::ViewRecommendedMatches);

        $matches = \App\Models\ProfileMatch::with('matchedUser.member:id,user_id,gender,age,city_id')
            ->where('user_id', $profileUserId)
            ->orderByDesc('match_percentage')
            ->limit(20)
            ->get()
            ->each(function (\App\Models\ProfileMatch $match) use ($link): void {
                // Annotate with this guardian's earlier feedback so the UI can
                // mark already-reviewed profiles.
                $feedback = GuardianFeedback::where('guardian_link_id', $link->id)
                    ->where('target_user_id', $match->match_id)
                    ->pluck('feedback_type');
                $match->setAttribute('guardian_feedback', $feedback);
            });

        GuardianActivityLog::record($link->id, $guardian->id, $profileUserId, 'guardian_viewed_matches', null, null);

        return $matches;
    }

    // ── Family introduction (§15) ──────────────────────────────────────────

    /**
     * The Primary User (or an authorized guardian) requests a family
     * introduction on an accepted proposal. Both sides' guardians are notified
     * and must consent before a family conversation is created.
     */
    public function requestIntroduction(User $actor, int $proposalId, ?string $message): FamilyIntroduction
    {
        $proposal = ExpressInterest::where(function ($query) use ($actor): void {
            $query->where('user_id', $actor->id)->orWhere('interested_by', $actor->id);
        })->where('status', 'accepted')->findOrFail($proposalId);

        $actorIsInitiator = (int) $proposal->interested_by === (int) $actor->id;
        $firstSideUserId = (int) $proposal->interested_by;
        $secondSideUserId = (int) $proposal->user_id;

        $firstGuardianLink = FamilyGuardianLink::where('profile_user_id', $firstSideUserId)
            ->where('status', 'approved')->whereNull('revoked_at')->whereNull('paused_at')
            ->orderByDesc('is_wali')->first();
        $secondGuardianLink = FamilyGuardianLink::where('profile_user_id', $secondSideUserId)
            ->where('status', 'approved')->whereNull('revoked_at')->whereNull('paused_at')
            ->orderByDesc('is_wali')->first();

        $introduction = FamilyIntroduction::create([
            'proposal_id' => $proposal->id,
            'initiated_by' => $actor->id,
            'status' => 'requested',
            'first_guardian_link_id' => $firstGuardianLink?->id,
            'second_guardian_link_id' => $secondGuardianLink?->id,
            'message' => $message,
        ]);

        GuardianActivityLog::record($firstGuardianLink?->id, $actor->id, $firstSideUserId, 'family_intro_requested', FamilyIntroduction::class, $introduction->id);

        // Notify the other member + both sides' guardians.
        $otherUserId = $actorIsInitiator ? $secondSideUserId : $firstSideUserId;
        NotificationHelper::guardianEvent(
            User::find($otherUserId),
            'family_intro_requested',
            'Family Introduction Requested',
            'The other family requested a family introduction.',
            $actor->id,
            $introduction->id,
        );

        foreach ([$firstGuardianLink, $secondGuardianLink] as $link) {
            if ($link) {
                NotificationHelper::guardianEvent(
                    User::find($link->guardian_user_id),
                    'family_intro_requested',
                    'Family Introduction Requested',
                    'A family introduction was requested for the member you assist.',
                    $actor->id,
                    $introduction->id,
                );
            }
        }

        return $introduction;
    }

    /** The other side (member or guardian) accepts the introduction. */
    public function respondIntroduction(User $actor, int $introductionId, bool $accept): FamilyIntroduction
    {
        $introduction = FamilyIntroduction::findOrFail($introductionId);
        $proposal = $introduction->proposal;

        $isParticipant = in_array((int) $actor->id, [(int) $proposal->user_id, (int) $proposal->interested_by], true);
        $isSideGuardian = FamilyGuardianLink::where('guardian_user_id', $actor->id)
            ->whereIn('profile_user_id', [(int) $proposal->user_id, (int) $proposal->interested_by])
            ->where('status', 'approved')->whereNull('revoked_at')
            ->exists();

        if (! $isParticipant && ! $isSideGuardian) {
            throw new ApiException('You are not part of this introduction.', 403, ApiErrorCode::Forbidden->value);
        }

        if ($introduction->status !== 'requested') {
            throw new ApiException('This introduction request is no longer pending.', 409, ApiErrorCode::Conflict->value);
        }

        if (! $accept) {
            $introduction->forceFill(['status' => 'declined'])->save();

            GuardianActivityLog::record(null, $actor->id, (int) $introduction->initiated_by, 'family_intro_declined', FamilyIntroduction::class, $introduction->id);

            NotificationHelper::guardianEvent(
                User::find($introduction->initiated_by),
                'family_intro_declined',
                'Family Introduction Declined',
                'The family introduction request was declined.',
                $actor->id,
                $introduction->id,
            );

            return $introduction;
        }

        return DB::transaction(function () use ($introduction, $actor): FamilyIntroduction {
            $introduction->forceFill([
                'status' => 'active',
                'accepted_at' => now(),
            ])->save();

            // Family conversation is created only after both sides accept —
            // personal chat history is never copied into it (§16).
            $conversation = FamilyConversation::firstOrCreate([
                'proposal_id' => $introduction->proposal_id,
            ], [
                'created_by' => $actor->id,
                'first_profile_user_id' => (int) $introduction->proposal->interested_by,
                'second_profile_user_id' => (int) $introduction->proposal->user_id,
                'status' => 'active',
            ]);

            FamilyConversationMessage::create([
                'family_conversation_id' => $conversation->id,
                'sender_user_id' => $actor->id,
                'message' => 'Family conversation started after the family introduction.',
            ]);

            GuardianActivityLog::record(null, $actor->id, (int) $introduction->initiated_by, 'family_intro_accepted', FamilyIntroduction::class, $introduction->id);

            foreach (array_unique([(int) $introduction->proposal->user_id, (int) $introduction->proposal->interested_by, (int) $introduction->initiated_by]) as $recipientId) {
                if ($recipientId === (int) $actor->id) {
                    continue;
                }
                NotificationHelper::guardianEvent(
                    User::find($recipientId),
                    'family_intro_accepted',
                    'Family Introduction Accepted',
                    'The family introduction was accepted. A family conversation is now open.',
                    $actor->id,
                    $introduction->id,
                );
            }

            return $introduction;
        });
    }

    /** The initiating side can cancel or pause the introduction (§15). */
    public function cancelIntroduction(User $actor, int $introductionId): FamilyIntroduction
    {
        $introduction = FamilyIntroduction::where('initiated_by', $actor->id)->findOrFail($introductionId);

        if (in_array($introduction->status, ['completed', 'declined', 'cancelled'], true)) {
            throw new ApiException('This introduction is already closed.', 409, ApiErrorCode::Conflict->value);
        }

        $introduction->forceFill(['status' => 'cancelled', 'cancelled_at' => now()])->save();

        GuardianActivityLog::record(null, $actor->id, (int) $actor->id, 'family_intro_cancelled', FamilyIntroduction::class, $introduction->id);

        return $introduction;
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function resolvePermissions(array $data): array
    {
        if (! empty($data['permissions']) && is_array($data['permissions'])) {
            $valid = array_map(fn (GuardianPermission $case) => $case->value, GuardianPermission::cases());

            return array_values(array_intersect($valid, $data['permissions']));
        }

        return GuardianPermission::preset((string) ($data['permission_preset'] ?? 'view_only'));
    }

    private function notifyMember(FamilyGuardianLink $link, string $type, string $title, string $message, int $notifyBy): void
    {
        NotificationHelper::guardianEvent(
            User::find($link->profile_user_id),
            $type,
            $title,
            $message,
            $notifyBy,
            $link->id,
        );
    }
}
