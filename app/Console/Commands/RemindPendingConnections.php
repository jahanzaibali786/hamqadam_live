<?php

namespace App\Console\Commands;

use App\Models\ExpressInterest;
use App\Models\MarriageProposal;
use App\Services\NotificationHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindPendingConnections extends Command
{
    protected $signature = 'connections:remind-pending {--limit=200}';
    protected $description = 'Send at most one pending-interest (24h) and pending-proposal (48h) reminder.';

    public function handle(): int
    {
        $limit=max(1,min(1000,(int)$this->option('limit')));
        $sent=0;
        ExpressInterest::query()->where('status',0)->whereNull('workflow_reminded_at')
            ->where('created_at','<=',now()->subHours(24))
            ->orderBy('id')->limit($limit)->pluck('id')->each(function ($id) use (&$sent) {
                $interest=DB::transaction(function () use ($id) {
                    $row=ExpressInterest::lockForUpdate()->find($id);
                    if (!$row || (int)($row->status instanceof \BackedEnum?$row->status->value:$row->status)!==0 ||
                        $row->workflow_reminded_at || $row->created_at->greaterThan(now()->subHours(24))) return null;
                    $row->workflow_reminded_at=now();$row->save();
                    return $row;
                });
                if (!$interest || !$interest->recipient) return;
                NotificationHelper::event($interest->recipient,'workflow_interest_reminder',[
                    'event_id'=>'interest-'.$interest->id,'info_id'=>$interest->id,'notify_by'=>$interest->interested_by,
                ]);
                $sent++;
            });

        MarriageProposal::query()->where('status','pending')->whereNull('reminded_at')
            ->where('created_at','<=',now()->subHours(48))
            ->orderBy('id')->limit($limit)->pluck('id')->each(function ($id) use (&$sent) {
                $proposal=DB::transaction(function () use ($id) {
                    $row=MarriageProposal::lockForUpdate()->find($id);
                    if (!$row || $row->status!=='pending' || $row->reminded_at ||
                        $row->created_at->greaterThan(now()->subHours(48))) return null;
                    $row->reminded_at=now();$row->save();return $row;
                });
                if (!$proposal || !$proposal->recipient) return;
                NotificationHelper::event($proposal->recipient,'workflow_proposal_reminder',[
                    'event_id'=>'proposal-'.$proposal->id,'info_id'=>$proposal->id,'notify_by'=>$proposal->sender_id,
                ]);
                $sent++;
            });
        foreach ([24 => 'workflow_family_meeting_reminder_24', 1 => 'workflow_family_meeting_reminder_1'] as $hours => $eventKey) {
            $column = $hours === 24 ? 'reminded_24h_at' : 'reminded_1h_at';
            \App\Models\ProposalMeeting::query()
                ->whereNull($column)->where('scheduled_at','>',now())
                ->where('scheduled_at','<=',now()->addHours($hours))
                ->whereNotIn('status',['cancelled','completed'])
                ->orderBy('id')->limit($limit)->pluck('id')->each(function ($id) use (&$sent,$column,$eventKey) {
                    $meeting=DB::transaction(function () use ($id,$column) {
                        $row=\App\Models\ProposalMeeting::lockForUpdate()->find($id);
                        if (!$row || $row->{$column} || $row->scheduled_at->lessThanOrEqualTo(now()) ||
                            in_array($row->status,['cancelled','completed'],true)) return null;
                        $accepted=MarriageProposal::where('interest_id',$row->express_interest_id)->where('status','accepted')->exists();
                        if (!$accepted) return null;
                        $row->{$column}=now();$row->save();return $row;
                    });
                    if (!$meeting) return;
                    $interest=$meeting->proposal;
                    foreach ([$interest?->sender,$interest?->recipient] as $recipient) {
                        if (!$recipient) continue;
                        NotificationHelper::event($recipient,$eventKey,[
                            'event_id'=>$eventKey.'-'.$meeting->id,
                            'info_id'=>$meeting->id,
                            'notify_by'=>$meeting->organizer_user_id,
                        ]);
                        $sent++;
                    }
                });
        }

        $this->info("Processed {$sent} one-time connection reminders.");
        return self::SUCCESS;
    }
}
