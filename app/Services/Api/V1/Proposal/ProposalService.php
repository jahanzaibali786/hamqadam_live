<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Proposal;

use App\Enums\ApiErrorCode;
use App\Enums\ProposalStatus;
use App\Exceptions\ApiException;
use App\Models\ChatThread;
use App\Models\ExpressInterest;
use App\Models\IgnoredUser;
use App\Models\PackageUsage;
use App\Models\ProposalEvent;
use App\Models\ProposalNote;
use App\Models\ProfileMatch;
use App\Models\Shortlist;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\NotificationHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProposalService
{
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $this->expireOverdueProposals($user);

        $query = ExpressInterest::query()
            ->with(['sender', 'recipient'])
            ->latest();

        match ($filters['direction'] ?? 'all') {
            'sent' => $query->where('interested_by', $user->id),
            'received' => $query->where('user_id', $user->id),
            default => $query->where(function ($scope) use ($user) {
                $scope->where('interested_by', $user->id)
                    ->orWhere('user_id', $user->id);
            }),
        };

        if (! empty($filters['status'])) {
            $query->where('status', $this->statusFromLabel($filters['status'])->value);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(User $sender, int $recipientId, ?string $note = null): ExpressInterest
    {
        if ($sender->id === $recipientId) {
            throw new ApiException('You cannot send a proposal to yourself.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $recipient = User::where('user_type', 'member')->whereKey($recipientId)->first();
        if (! $recipient) {
            throw new ApiException('The selected member was not found.', 404, ApiErrorCode::NotFound->value);
        }

        if ($this->isIgnoredBetween($sender->id, $recipientId)) {
            throw new ApiException('This member is not available for proposals.', 409, ApiErrorCode::Conflict->value);
        }

        if ($recipient->profile_privacy_setting?->do_not_disturb) {
            throw new ApiException('This member is not accepting proposals right now.', 409, ApiErrorCode::Conflict->value);
        }

        if ($this->hasActiveProposal($sender->id, $recipientId)) {
            throw new ApiException('A proposal already exists between these members.', 409, ApiErrorCode::Conflict->value);
        }

        if (! $sender->member || (int) $sender->member->remaining_interest <= 0) {
            throw new ApiException('Please upgrade your package to send more proposals.', 402, 'package_limit_exceeded');
        }

        $proposal = DB::transaction(function () use ($sender, $recipientId, $note) {
            $sender->member()->lockForUpdate()->first()->decrement('remaining_interest');
            $expiryDays = max(1, (int) (get_setting('proposal_expiry_days') ?: 14));
            $compatibility = ProfileMatch::where('user_id', $sender->id)
                ->where('match_id', $recipientId)
                ->value('match_percentage');

            $proposal = ExpressInterest::create([
                'user_id' => $recipientId,
                'interested_by' => $sender->id,
                'status' => ProposalStatus::Pending,
                'initial_note' => $note,
                'expires_at' => now()->addDays($expiryDays),
                'compatibility_snapshot' => $compatibility,
            ]);

            $this->recordEvent($proposal, $sender, 'proposal_sent', $note);

            return $proposal->load(['sender', 'recipient', 'events.actor', 'notes.user']);
        });

        // Outside the transaction: a notification that fails must not undo the
        // proposal, and the recipient should not be told about one that was
        // rolled back. Until this existed the whole proposal flow was silent -
        // no push and no notification row - so the member only found out by
        // opening the app and looking at the list.
        NotificationHelper::proposalReceived($recipient, $sender, (int) $proposal->id);

        return $proposal;
    }

    public function accept(User $actor, int $proposalId, ?string $note = null): ExpressInterest
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);
        $this->expireIfOverdue($proposal);
        $this->ensureRecipient($actor, $proposal);
        $this->ensurePending($proposal);

        $accepted = DB::transaction(function () use ($actor, $proposal, $note) {
            $proposal->forceFill([
                'status' => ProposalStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            $this->ensureChatThread($proposal);
            $this->recordEvent($proposal, $actor, 'proposal_accepted', $note);

            return $proposal->fresh(['sender', 'recipient', 'events.actor', 'notes.user']);
        });

        if ($accepted->sender) {
            NotificationHelper::proposalAccepted($accepted->sender, $actor, (int) $accepted->id);
        }

        return $accepted;
    }

    public function reject(User $actor, int $proposalId, ?string $note = null): ExpressInterest
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);
        $this->expireIfOverdue($proposal);
        $this->ensureRecipient($actor, $proposal);
        $this->ensurePending($proposal);

        $rejected = $this->transition($proposal, $actor, ProposalStatus::Rejected, 'proposal_rejected', $note, [
            'responded_at' => now(),
        ]);

        if ($rejected->sender) {
            NotificationHelper::proposalRejected($rejected->sender, $actor, (int) $rejected->id);
        }

        return $rejected;
    }

    public function withdraw(User $actor, int $proposalId, ?string $note = null): ExpressInterest
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);
        $this->expireIfOverdue($proposal);
        $this->ensureSender($actor, $proposal);
        $this->ensurePending($proposal);

        return $this->transition($proposal, $actor, ProposalStatus::Withdrawn, 'proposal_withdrawn', $note, [
            'withdrawn_at' => now(),
        ]);
    }

    public function cancel(User $actor, int $proposalId, ?string $note = null): ExpressInterest
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);
        $this->expireIfOverdue($proposal);
        $this->ensureSender($actor, $proposal);
        $this->ensurePending($proposal);

        return $this->transition($proposal, $actor, ProposalStatus::Cancelled, 'proposal_cancelled', $note, [
            'cancelled_at' => now(),
        ]);
    }

    public function addNote(User $actor, int $proposalId, string $note): ProposalNote
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);

        return DB::transaction(function () use ($actor, $proposal, $note) {
            $proposalNote = ProposalNote::create([
                'express_interest_id' => $proposal->id,
                'user_id' => $actor->id,
                'note' => $note,
            ]);

            $this->recordEvent($proposal, $actor, 'proposal_note_added');

            return $proposalNote->load('user');
        });
    }

    public function timeline(User $actor, int $proposalId)
    {
        $proposal = $this->proposalForParticipant($actor, $proposalId);

        return $proposal->events()->with('actor')->oldest()->get();
    }

    public function favourite(User $actor, int $userId): Shortlist
    {
        $this->ensureTargetUser($actor, $userId);

        return Shortlist::firstOrCreate([
            'user_id' => $userId,
            'shortlisted_by' => $actor->id,
        ]);
    }

    public function favourites(User $actor, int $perPage = 20): LengthAwarePaginator
    {
        return Shortlist::with([
            'user.member',
            'user.physical_attributes',
            'user.spiritual_backgrounds',
            'user.addresses',
            'user.profile_match_for_viewer' => fn ($query) => $query->where('user_id', $actor->id),
        ])
            ->where('shortlisted_by', $actor->id)
            ->latest()
            ->paginate($perPage);
    }

    public function shortlists(User $actor, int $perPage = 20): LengthAwarePaginator
    {
        return Shortlist::with([
            'user.member',
            'user.physical_attributes',
            'user.spiritual_backgrounds',
            'user.addresses',
            'user.profile_match_for_viewer' => fn ($query) => $query->where('user_id', $actor->id),
        ])
            ->where('shortlisted_by', $actor->id)
            ->latest()
            ->paginate($perPage);
    }

    public function isFavourite(User $actor, int $userId): bool
    {
        return Shortlist::where('user_id', $userId)->where('shortlisted_by', $actor->id)->exists();
    }

    public function removeFavourite(User $actor, int $userId): void
    {
        Shortlist::where('user_id', $userId)->where('shortlisted_by', $actor->id)->delete();
    }

    public function removeShortlist(User $actor, int $userId): void
    {
        Shortlist::where('user_id', $userId)->where('shortlisted_by', $actor->id)->delete();
    }

    public function shortlist(User $actor, int $userId): Shortlist
    {
        $target = $this->ensureTargetUser($actor, $userId);

        if ($this->isShortlisted($actor, $userId)) {
            throw new ApiException('This member is already in your shortlist.', 409, ApiErrorCode::Conflict->value);
        }

        if (! $this->hasAcceptedInterestBetween($actor->id, $userId)) {
            throw new ApiException('Please wait for an accepted interest before shortlisting this member.', 409, ApiErrorCode::Conflict->value);
        }

        $coinCost = (int) feature_coin_cost('shortlist', 5);
        $balance = (int) ($actor->member?->remaining_interest ?? 0);

        if ($balance < $coinCost) {
            throw new ApiException(
                'You need '.$coinCost.' coin(s) to shortlist this member and you currently have '.$balance.'.',
                402,
                'insufficient_coins'
            );
        }

        return DB::transaction(function () use ($actor, $target, $userId, $coinCost) {
            $member = $actor->member()->lockForUpdate()->first();
            $currentBalance = (int) ($member?->remaining_interest ?? 0);

            if ($currentBalance < $coinCost) {
                throw new ApiException(
                    'You need '.$coinCost.' coin(s) to shortlist this member and you currently have '.$currentBalance.'.',
                    402,
                    'insufficient_coins'
                );
            }

            $shortlist = Shortlist::create([
                'user_id' => $userId,
                'shortlisted_by' => $actor->id,
            ]);

            $member->remaining_interest = $currentBalance - $coinCost;
            $member->save();

            PackageUsage::record(
                $actor->id,
                'shortlist',
                'Shortlist',
                $coinCost,
                Shortlist::class,
                $shortlist->id,
                'Used '.$coinCost.' coin(s) to shortlist member.'
            );

            try {
                $notifyType = 'shortlist';
                $notifyId = unique_notify_id();
                $message = $actor->first_name.' '.$actor->last_name.' '.translate(' has shortlisted you.');
                Notification::send(
                    $target,
                    new DbStoreNotification($notifyType, $notifyId, $actor->id, $shortlist->id, $message, route('my_shortlists'))
                );
            } catch (\Throwable $e) {
                // Notification failure must not roll back the shortlist.
            }

            return $shortlist;
        });
    }

    public function isShortlisted(User $actor, int $userId): bool
    {
        return Shortlist::where('user_id', $userId)->where('shortlisted_by', $actor->id)->exists();
    }

    private function hasAcceptedInterestBetween(int $firstUserId, int $secondUserId): bool
    {
        return ExpressInterest::where('status', ProposalStatus::Accepted->value)
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where(function ($scope) use ($firstUserId, $secondUserId) {
                    $scope->where('interested_by', $firstUserId)->where('user_id', $secondUserId);
                })->orWhere(function ($scope) use ($firstUserId, $secondUserId) {
                    $scope->where('interested_by', $secondUserId)->where('user_id', $firstUserId);
                });
            })->exists();
    }

    public function ignore(User $actor, int $userId): IgnoredUser
    {
        $this->ensureTargetUser($actor, $userId);

        return IgnoredUser::firstOrCreate([
            'user_id' => $userId,
            'ignored_by' => $actor->id,
        ]);
    }

    public function removeIgnore(User $actor, int $userId): void
    {
        IgnoredUser::where('user_id', $userId)->where('ignored_by', $actor->id)->delete();
    }

    private function transition(
        ExpressInterest $proposal,
        User $actor,
        ProposalStatus $status,
        string $event,
        ?string $note,
        array $timestamps
    ): ExpressInterest {
        return DB::transaction(function () use ($proposal, $actor, $status, $event, $note, $timestamps) {
            $proposal->forceFill(['status' => $status] + $timestamps)->save();
            $this->recordEvent($proposal, $actor, $event, $note);

            return $proposal->fresh(['sender', 'recipient', 'events.actor', 'notes.user']);
        });
    }

    private function proposalForParticipant(User $actor, int $proposalId): ExpressInterest
    {
        $proposal = ExpressInterest::with(['sender', 'recipient', 'events.actor', 'notes.user'])->find($proposalId);

        if (! $proposal || ! in_array($actor->id, [(int) $proposal->interested_by, (int) $proposal->user_id], true)) {
            throw new ApiException('Proposal not found.', 404, ApiErrorCode::NotFound->value);
        }

        return $proposal;
    }

    private function ensureSender(User $actor, ExpressInterest $proposal): void
    {
        if ((int) $proposal->interested_by !== (int) $actor->id) {
            throw new ApiException('Only the sender can perform this action.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function ensureRecipient(User $actor, ExpressInterest $proposal): void
    {
        if ((int) $proposal->user_id !== (int) $actor->id) {
            throw new ApiException('Only the recipient can perform this action.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function ensurePending(ExpressInterest $proposal): void
    {
        if ($proposal->status !== ProposalStatus::Pending) {
            throw new ApiException('Only pending proposals can be changed.', 409, ApiErrorCode::Conflict->value);
        }
    }

    private function expireOverdueProposals(User $user): void
    {
        ExpressInterest::where('status', ProposalStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where(function ($query) use ($user) {
                $query->where('interested_by', $user->id)->orWhere('user_id', $user->id);
            })
            ->get()
            ->each(fn (ExpressInterest $proposal) => $this->expireIfOverdue($proposal));
    }

    private function expireIfOverdue(ExpressInterest $proposal): void
    {
        if ($proposal->status !== ProposalStatus::Pending || ! $proposal->expires_at || $proposal->expires_at->isFuture()) {
            return;
        }

        $proposal->forceFill([
            'status' => ProposalStatus::Expired,
            'expired_at' => now(),
        ])->save();

        $this->recordEvent($proposal, User::find($proposal->interested_by) ?: new User(), 'proposal_expired');
    }

    private function ensureTargetUser(User $actor, int $userId): User
    {
        if ($actor->id === $userId) {
            throw new ApiException('You cannot perform this action on yourself.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $target = User::where('user_type', 'member')->whereKey($userId)->first();
        if (! $target) {
            throw new ApiException('The selected member was not found.', 404, ApiErrorCode::NotFound->value);
        }

        return $target;
    }

    private function hasActiveProposal(int $firstUserId, int $secondUserId): bool
    {
        return ExpressInterest::whereIn('status', [ProposalStatus::Pending->value, ProposalStatus::Accepted->value])
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where(function ($scope) use ($firstUserId, $secondUserId) {
                    $scope->where('interested_by', $firstUserId)->where('user_id', $secondUserId);
                })->orWhere(function ($scope) use ($firstUserId, $secondUserId) {
                    $scope->where('interested_by', $secondUserId)->where('user_id', $firstUserId);
                });
            })->exists();
    }

    private function isIgnoredBetween(int $firstUserId, int $secondUserId): bool
    {
        return IgnoredUser::where(function ($query) use ($firstUserId, $secondUserId) {
            $query->where('ignored_by', $firstUserId)->where('user_id', $secondUserId);
        })->orWhere(function ($query) use ($firstUserId, $secondUserId) {
            $query->where('ignored_by', $secondUserId)->where('user_id', $firstUserId);
        })->exists();
    }

    private function ensureChatThread(ExpressInterest $proposal): void
    {
        $exists = ChatThread::where(function ($query) use ($proposal) {
            $query->where('sender_user_id', $proposal->interested_by)->where('receiver_user_id', $proposal->user_id);
        })->orWhere(function ($query) use ($proposal) {
            $query->where('sender_user_id', $proposal->user_id)->where('receiver_user_id', $proposal->interested_by);
        })->exists();

        if (! $exists) {
            $chatThread = new ChatThread();
            $chatThread->thread_code = $proposal->interested_by . now()->format('Ymd') . $proposal->user_id;
            $chatThread->sender_user_id = $proposal->interested_by;
            $chatThread->receiver_user_id = $proposal->user_id;
            $chatThread->save();
        }
    }

    private function recordEvent(ExpressInterest $proposal, User $actor, string $event, ?string $note = null): ProposalEvent
    {
        return ProposalEvent::create([
            'express_interest_id' => $proposal->id,
            'actor_id' => $actor->id,
            'event' => $event,
            'note' => $note,
        ]);
    }

    private function statusFromLabel(string $status): ProposalStatus
    {
        return match ($status) {
            'accepted' => ProposalStatus::Accepted,
            'rejected' => ProposalStatus::Rejected,
            'withdrawn' => ProposalStatus::Withdrawn,
            'cancelled' => ProposalStatus::Cancelled,
            'expired' => ProposalStatus::Expired,
            default => ProposalStatus::Pending,
        };
    }
}
