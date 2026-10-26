<?php

namespace App\Services;

use App\Models\ExpressInterest;
use App\Models\MarriageProposal;
use App\Models\User;
use App\Models\WorkflowMessageTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberConnectionWorkflow
{
    public function interestBetween(int $a, int $b): ?ExpressInterest
    {
        return ExpressInterest::where('interaction_type','interest')->where(function ($query) use ($a, $b) {
            $query->where(fn ($q) => $q->where('interested_by',$a)->where('user_id',$b))
                ->orWhere(fn ($q) => $q->where('interested_by',$b)->where('user_id',$a));
        })->where('status',1)->latest('id')->first();
    }

    /**
     * Previously, accepted marriage proposals were stored in express_interests
     * and were allowed to open a chat. The 2026-10-26 interaction_type backfill
     * reclassified those rows to "proposal", while the new communication gate
     * only searched "interest": already accepted members were then rejected.
     *
     * The compatibility path is intentionally narrow: an accepted legacy
     * proposal must have an acceptance timestamp or acceptance event AND an existing
     * chat between these same two members. Pending, declined and unaccepted
     * proposals never unlock communication.
     */
    public function hasAcceptedConnection(int $a, int $b): bool
    {
        if ($a === $b) return false;
        if ($this->interestBetween($a, $b)) return true;

        $pair = static function ($query) use ($a, $b): void {
            $query->where(function ($both) use ($a, $b) {
                $both->where(fn ($side) => $side->where('interested_by', $a)->where('user_id', $b))
                    ->orWhere(fn ($side) => $side->where('interested_by', $b)->where('user_id', $a));
            });
        };

        if (!ExpressInterest::query()->where('interaction_type', 'proposal')
            ->where('status', \App\Enums\ProposalStatus::Accepted->value)
            ->where($pair)
            ->where(function ($query) {
                $query->whereNotNull('responded_at')
                    ->orWhereHas('events', fn ($event) => $event->where('event', 'proposal_accepted'));
            })
            ->exists()) {
            return false;
        }

        return \App\Models\ChatThread::query()->where(function ($query) use ($a, $b) {
            $query->where(fn ($side) => $side->where('sender_user_id', $a)->where('receiver_user_id', $b))
                ->orWhere(fn ($side) => $side->where('sender_user_id', $b)->where('receiver_user_id', $a));
        })->exists();
    }

    /**
     * Null means messaging is permitted. Otherwise return a precise reason
     * instead of misleading an accepted member with "interest not accepted".
     * When a specific thread is supplied, inspect only that thread: a stale
     * inactive conversation elsewhere between the pair must not block it.
     */
    public function communicationDenialReason(int $a, int $b, ?\App\Models\ChatThread $thread = null): ?string
    {
        if ($a === $b) return 'You cannot message your own profile.';
        if (!$this->hasAcceptedConnection($a, $b)) {
            return 'Chat requires an accepted interest. If your interest is already accepted, please contact support to review the connection record.';
        }
        if ($this->isBlocked($a, $b)) {
            return 'Messaging is unavailable because one of these profiles is blocked or ignored.';
        }

        if ($thread) {
            $participants = [(int) $thread->sender_user_id, (int) $thread->receiver_user_id];
            if (!in_array($a, $participants, true) || !in_array($b, $participants, true)) {
                return 'This conversation does not belong to the selected members.';
            }
            if ($thread->blocked_by_user !== null) {
                return 'This conversation is blocked. Unblock it before sending messages.';
            }
            if ($thread->active !== null && (int) $thread->active === 0) {
                return 'This conversation is inactive. Contact support if your connection is still accepted.';
            }
            return null;
        }

        if (\App\Models\ChatThread::query()->where(function ($query) use ($a, $b) {
            $query->where(fn ($side) => $side->where('sender_user_id', $a)->where('receiver_user_id', $b))
                ->orWhere(fn ($side) => $side->where('sender_user_id', $b)->where('receiver_user_id', $a));
        })->whereNotNull('blocked_by_user')->exists()) {
            return 'This conversation is blocked. Unblock it before sending messages.';
        }
        return null;
    }

    public function canCommunicate(int $a, int $b, ?\App\Models\ChatThread $thread = null): bool
    {
        return $this->communicationDenialReason($a, $b, $thread) === null;
    }

    public function canCall(int $a, int $b, string $type): bool
    {
        if (!$this->canCommunicate($a,$b)) return false;
        $column = $type === 'video' ? 'allow_video_calls' : 'allow_audio_calls';
        return !\App\Models\MemberCommunicationPreference::whereIn('user_id',[$a,$b])->where($column,false)->exists();
    }

    public function createInterest(User $sender, User $recipient, WorkflowMessageTemplate $template): ExpressInterest
    {
        if ($template->kind !== 'interest' || !$template->is_active || $sender->id === $recipient->id) {
            throw ValidationException::withMessages(['template_id'=>'Invalid interest template or member.']);
        }
        if ($recipient->user_type !== 'member' || $recipient->blocked || $recipient->deactivated || $recipient->approved != 1 || $recipient->member?->hide_profile ||
            $recipient->profile_privacy_setting?->do_not_disturb || $this->isBlocked($sender->id,$recipient->id)) {
            throw ValidationException::withMessages(['member'=>'This profile is unavailable.']);
        }
        $interest = DB::transaction(function () use ($sender,$recipient,$template) {
            // Lock the two users in stable order to serialize opposite-direction requests.
            User::whereIn('id',[$sender->id,$recipient->id])->orderBy('id')->lockForUpdate()->get();
            $exists = ExpressInterest::where('interaction_type','interest')->where(function ($q) use ($sender,$recipient) {
                $q->where(fn ($side) => $side->where('interested_by',$sender->id)->where('user_id',$recipient->id))
                  ->orWhere(fn ($side) => $side->where('interested_by',$recipient->id)->where('user_id',$sender->id));
            })->whereIn('status',[0,1])->exists();
            if ($exists) throw ValidationException::withMessages(['member'=>'An active interest already exists between these members.']);
            $member = $sender->member()->lockForUpdate()->first();
            $cost = max(0,(int) feature_coin_cost('express_interest',1));
            if (!$member || $member->remaining_interest < $cost) throw ValidationException::withMessages(['member'=>'Insufficient coins to send interest.']);
            $member->remaining_interest -= $cost; $member->save();
            $interest = ExpressInterest::create(['user_id'=>$recipient->id,'interested_by'=>$sender->id,'interaction_type'=>'interest','status'=>0,'initial_note'=>$template->complete_message,'workflow_template_id'=>$template->id]);
            if ($cost > 0) \App\Models\PackageUsage::record($sender->id,'interest','Express Interest',$cost,ExpressInterest::class,$interest->id,'Interest sent via managed template');
            return $interest;
        });
        NotificationHelper::interestReceived($recipient,$sender,$interest->id);
        return $interest;
    }

    public function respondInterest(User $actor, ExpressInterest $interest, bool $accept): ExpressInterest
    {
        if ((int)$interest->user_id !== (int)$actor->id || $interest->interaction_type !== 'interest') abort(403);
        DB::transaction(function () use ($interest,$accept) {
            $current = ExpressInterest::lockForUpdate()->findOrFail($interest->id);
            if ((int)($current->status instanceof \BackedEnum ? $current->status->value : $current->status) !== 0) {
                throw ValidationException::withMessages(['interest'=>'This interest has already been answered.']);
            }
            $current->status=$accept?1:2;$current->responded_at=now();$current->save();
            if ($accept) {
                \App\Models\ChatThread::firstOrCreate([
                    'sender_user_id'=>$current->interested_by,'receiver_user_id'=>$current->user_id
                ],['thread_code'=>$current->interested_by.now()->format('YmdHis').$current->user_id,'active'=>1]);
            }
        });
        if ($accept) NotificationHelper::interestAccepted($interest->sender,$actor,$interest->id);
        else NotificationHelper::event($interest->sender,'interest_declined',['event_id'=>(string)$interest->id,'info_id'=>$interest->id,'notify_by'=>$actor->id]);
        return $interest->refresh();
    }

    public function saveProposal(User $sender, User $recipient, array $data, bool $send = false): MarriageProposal
    {
        $interest=$this->interestBetween($sender->id,$recipient->id);
        if (!$interest || !$this->canCommunicate($sender->id,$recipient->id)) throw ValidationException::withMessages(['proposal'=>'Send Interest First. A proposal requires an accepted interest.']);
        $template=null;
        if (!empty($data['template_id'])) {
            $template=WorkflowMessageTemplate::active('proposal',$data['language'] ?? 'en')->find($data['template_id']);
            if (!$template) throw ValidationException::withMessages(['template_id'=>'This proposal template is unavailable.']);
        }
        $message=trim((string)($data['message']??$template?->complete_message??''));
        if (mb_strlen($message)<20 || mb_strlen($message)>5000) throw ValidationException::withMessages(['message'=>'Proposal message must contain 20–5000 characters.']);
        if (!in_array($data['source']??'static',['static','ai'],true)) throw ValidationException::withMessages(['source'=>'Invalid message source.']);
        if (($data['source']??'static')==='ai' && empty($data['approved'])) throw ValidationException::withMessages(['approved'=>'Review and approve AI content before saving.']);
        $proposal=DB::transaction(function () use ($interest,$sender,$recipient,$data,$template,$message,$send) {
            User::whereIn('id',[$sender->id,$recipient->id])->orderBy('id')->lockForUpdate()->get();
            $active=MarriageProposal::where('interest_id',$interest->id)->whereIn('status',['pending','accepted'])->exists();
            if ($active) throw ValidationException::withMessages(['proposal'=>'An active proposal already exists.']);
            $draft=MarriageProposal::where('interest_id',$interest->id)->where('sender_id',$sender->id)->where('status','draft')->latest('id')->first();
            if ($send && empty($data['approved'])) throw ValidationException::withMessages(['approved'=>'Please approve the final proposal text before sending.']);
            $attrs=['sender_id'=>$sender->id,'recipient_id'=>$recipient->id,'interest_id'=>$interest->id,'template_id'=>$template?->id,'language'=>$data['language']??'en','source'=>$data['source']??'static','message'=>$message,'status'=>$send?'pending':'draft','approved_at'=>$send?now():null];
            if ($draft) {$draft->fill($attrs)->save(); return $draft;}
            return MarriageProposal::create($attrs);
        });
        if ($send) NotificationHelper::proposalReceived($recipient,$sender,$proposal->id);
        return $proposal;
    }

    public function answerProposal(User $actor, MarriageProposal $proposal, bool $accept): MarriageProposal
    {
        if ((int)$proposal->recipient_id !== (int)$actor->id || !$this->interestBetween($proposal->sender_id,$proposal->recipient_id)) abort(403);
        DB::transaction(function () use ($proposal,$accept) {
            $current=MarriageProposal::lockForUpdate()->findOrFail($proposal->id);
            if ($current->status!=='pending') throw ValidationException::withMessages(['proposal'=>'Proposal was already answered.']);
            $current->status=$accept?'accepted':'declined';
            $current->responded_at=now();
            $current->family_stage=$accept?'discussion':null;
            $current->save();
        });
        if ($accept) NotificationHelper::proposalAccepted($proposal->sender,$actor,$proposal->id);
        else NotificationHelper::event($proposal->sender,'proposal_declined',['event_id'=>(string)$proposal->id,'info_id'=>$proposal->id,'notify_by'=>$actor->id]);
        return $proposal->refresh();
    }

    private function isBlocked(int $a,int $b): bool
    {
        return \App\Models\IgnoredUser::where(function ($query) use ($a,$b) {
            $query->where(fn($q)=>$q->where('ignored_by',$a)->where('user_id',$b))
                ->orWhere(fn($q)=>$q->where('ignored_by',$b)->where('user_id',$a));
        })->exists();
    }
}
