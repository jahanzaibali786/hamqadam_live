<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Services\FcmV1Service;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindUnreadNotifications extends Command
{
    protected $signature = 'notifications:remind-unread {--limit=500}';
    protected $description = 'Send bounded reminders for unread push notifications, respecting quiet hours.';

    public function handle(): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $sent = 0;
        $scanned = 0;
        $notices = Notification::query()->whereNull('read_at')
            ->where('created_at','>=',now()->subDays(30))->orderBy('id')->cursor();
        foreach ($notices as $notification) {
            if (++$scanned > $limit * 20 || $sent >= $limit) break;
            // Re-read before dispatch because a member may have opened it meanwhile.
            if (Notification::whereKey($notification->id)->whereNull('read_at')->doesntExist()) continue;
            $user = $notification->notifiable;
            if (! $user || ! method_exists($user, 'member')) continue;
            $pref = NotificationPreference::query()->firstOrCreate(['user_id'=>$user->id]);
            if (! $pref->push_enabled) continue;
            $data = (array) $notification->data;
            if (! $pref->engagement_enabled && in_array((string)($data['type'] ?? ''), ['engagement','profile_view','match_recommendation'], true)) continue;
            if (! ($data['critical'] ?? false) && $this->isQuiet($pref)) continue;
            $log = NotificationDeliveryLog::query()->where('notification_id',(string) $notification->id)
                ->where('user_id',$user->id)->where('channel','push')->latest('id')->first();
            $attempts = (int) ($log?->reminder_count ?? 0);
            $max = min(10, max(0, (int) $pref->reminder_max_attempts));
            if ($attempts >= $max) continue;
            $last = $log?->last_reminded_at ?? $log?->sent_at ?? $notification->created_at;
            if ($last && $last->gt(now()->subMinutes(max(60, (int) $pref->reminder_interval_minutes)))) continue;
            try {
                if (! FcmV1Service::sendToUser((int)$user->id, [
                    'title' => (string)($data['title'] ?? 'Hamqadam'),
                    'body' => (string)($data['message'] ?? 'You have an unread notification.'),
                ], [
                    'type' => (string)($data['type'] ?? 'reminder'),
                    'info_id' => (string)($data['info_id'] ?? ''),
                    'route' => (string)($data['route'] ?? '/'),
                ])) {
                    throw new \RuntimeException('Push provider reported no successful delivery.');
                }
                if ($log) {
                    $log->update(['reminder_count'=>$attempts+1,'last_reminded_at'=>now(),'status'=>'sent']);
                } else {
                    NotificationDeliveryLog::query()->create([
                        'notification_id'=>(string)$notification->id,
                        'user_id'=>$user->id,
                        'channel'=>'push',
                        'status'=>'sent',
                        'payload'=>$data,
                        'sent_at'=>now(),
                        'reminder_count'=>1,
                        'last_reminded_at'=>now(),
                        'deep_link'=>$data['route'] ?? '/',
                    ]);
                }
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
                if ($log) {
                    $log->update(['failure_reason'=>mb_substr($exception->getMessage(),0,1000),'status'=>'failed']);
                } else {
                    NotificationDeliveryLog::query()->create([
                        'notification_id'=>(string)$notification->id,
                        'user_id'=>$user->id,
                        'channel'=>'push',
                        'status'=>'failed',
                        'payload'=>$data,
                        'failure_reason'=>mb_substr($exception->getMessage(),0,1000),
                        'reminder_count'=>1,
                        'last_reminded_at'=>now(),
                        'deep_link'=>$data['route'] ?? '/',
                    ]);
                }
            }
        }
        $this->info('Unread push reminders sent: '.$sent.'; records scanned: '.min($scanned,$limit*20));
        return self::SUCCESS;
    }

    private function isQuiet(NotificationPreference $pref): bool
    {
        $start = (string)($pref->quiet_hours_start ?? '');
        $end = (string)($pref->quiet_hours_end ?? '');
        if ($start === '' || $end === '') return false;
        try {
            $clock = Carbon::now((string)($pref->timezone ?: 'Asia/Karachi'))->format('H:i:s');
        } catch (\Throwable $exception) {
            $clock = Carbon::now('UTC')->format('H:i:s');
        }
        $start = substr($start,0,8);
        $end = substr($end,0,8);
        if (strlen($start) === 5) $start .= ':00';
        if (strlen($end) === 5) $end .= ':00';
        if ($start === $end) return false;
        return $start < $end ? ($clock >= $start && $clock < $end) : ($clock >= $start || $clock < $end);
    }
}
