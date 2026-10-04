<?php
namespace App\Console\Commands;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Services\FcmV1Service;
use Illuminate\Console\Command;
class RemindUnreadNotifications extends Command
{
    protected $signature='notifications:remind-unread {--limit=500}';
    protected $description='Re-send eligible unread push notifications using per-user reminder limits.';
    public function handle(): int
    {
        $rows=Notification::whereNull('read_at')->oldest()->limit((int)$this->option('limit'))->get(); $sent=0;
        foreach($rows as $notification){
            $user=$notification->notifiable; if(!$user) continue;
            $pref=NotificationPreference::firstOrCreate(['user_id'=>$user->id]); if(!$pref->push_enabled) continue;
            $log=NotificationDeliveryLog::where('notification_id',(string)$notification->id)->where('user_id',$user->id)->where('channel','push')->latest()->first();
            $count=(int)($log?->reminder_count??0); if($count >= (int)$pref->reminder_max_attempts) continue;
            $last=$log?->last_reminded_at ?? $log?->sent_at ?? $notification->created_at;
            if($last && $last->gt(now()->subMinutes((int)$pref->reminder_interval_minutes))) continue;
            $data=(array)$notification->data; try {
                FcmV1Service::sendToUser((int)$user->id,['title'=>$data['title']??'Hamqadam','body'=>$data['message']??'You have an unread notification.'],['type'=>$data['type']??'reminder','info_id'=>(string)($data['info_id']??''),'route'=>$data['route']??'/']);
                if($log){$log->update(['reminder_count'=>$count+1,'last_reminded_at'=>now(),'status'=>'sent']);} else {NotificationDeliveryLog::create(['notification_id'=>(string)$notification->id,'user_id'=>$user->id,'channel'=>'push','status'=>'sent','payload'=>$data,'sent_at'=>now(),'reminder_count'=>1,'last_reminded_at'=>now(),'deep_link'=>$data['route']??'/']);}
                $sent++;
            } catch(\Throwable $e){ if($log){$log->update(['failure_reason'=>mb_substr($e->getMessage(),0,1000),'status'=>'failed']);} }
        }
        $this->info("Unread notification reminders sent: {$sent}"); return self::SUCCESS;
    }
}
