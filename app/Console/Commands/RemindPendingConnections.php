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
        $this->info("Processed {$sent} one-time connection reminders.");
        return self::SUCCESS;
    }
}
