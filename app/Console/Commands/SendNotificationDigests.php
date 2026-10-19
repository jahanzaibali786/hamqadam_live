<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/** Email-only daily/weekly digest. Push notifications remain separate. */
class SendNotificationDigests extends Command
{
    protected $signature = 'notifications:send-digests {--limit=200}';
    protected $description = 'Send opt-in unread-notification digests once per recipient/period.';

    public function handle(): int
    {
        if (! Schema::hasTable('notification_digest_runs') || ! Schema::hasTable('notification_preferences')) {
            $this->warn('Run digest table migrations first.');
            return self::FAILURE;
        }
        $limit = max(1, min(2000, (int) $this->option('limit')));
        $sent = 0;
        $prefs = NotificationPreference::query()->where('email_enabled', true)
            ->whereIn('digest_frequency', ['daily', 'weekly'])->orderBy('id')->cursor();
        foreach ($prefs as $pref) {
            if ($sent >= $limit) break;
            $user = User::query()->whereKey($pref->user_id)->whereNotNull('email_verified_at')->first();
            if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) continue;
            try { $now = Carbon::now((string) ($pref->timezone ?: 'Asia/Karachi')); }
            catch (\Throwable) { $now = Carbon::now('Asia/Karachi'); }
            // No digest during quiet hours, or late at night; only local 09:00–10:59.
            if ($now->hour < 9 || $now->hour > 10) continue;
            if ($pref->digest_frequency === 'weekly' && $now->dayOfWeekIso !== 1) continue;
            $frequency = $pref->digest_frequency;
            $periodDate = $frequency === 'weekly' ? $now->copy()->startOfWeek()->toDateString() : $now->toDateString();
            $span = $frequency === 'weekly' ? 8 : 2;
            $unread = Notification::query()->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->id)->whereNull('read_at')
                ->where('created_at', '>=', now()->subDays($span))->latest('created_at')->limit(15)->get();
            if ($unread->isEmpty()) continue;
            // DB uniqueness prevents duplicate digests across concurrent scheduler runs.
            $existing = DB::table('notification_digest_runs')->where('user_id', $user->id)
                ->where('frequency', $frequency)->where('period_date', $periodDate)->first();
            if ($existing && ($existing->status === 'sent' || $existing->status === 'pending' &&
                Carbon::parse($existing->updated_at)->greaterThan(now()->subHours(3)))) continue;
            if ($existing) {
                DB::table('notification_digest_runs')->where('id', $existing->id)->update([
                    'status'=>'pending','updated_at'=>now(),'failure_reason'=>null,
                ]);
            } else {
                try {
                    DB::table('notification_digest_runs')->insert([
                        'user_id'=>$user->id,'frequency'=>$frequency,'period_date'=>$periodDate,
                        'status'=>'pending','notification_count'=>$unread->count(),'created_at'=>now(),'updated_at'=>now(),
                    ]);
                } catch (\Illuminate\Database\QueryException) { continue; }
            }
            $lines = ["Hamqadam: your {$frequency} unread-notification digest", ''];
            foreach ($unread as $notice) {
                $payload = (array) $notice->data;
                $title = preg_replace('/\s+/', ' ', strip_tags((string) ($payload['title'] ?? 'Notification')));
                $lines[] = '• '.mb_substr($title, 0, 150);
            }
            $lines[] = '';
            $lines[] = 'Open your notification center: '.route('client.scope.notifications');
            $lines[] = 'Manage notification preferences in your account settings.';
            try {
                Mail::raw(implode("\n", $lines), fn ($message) => $message->to($user->email)->subject('Hamqadam — unread notifications'));
                DB::table('notification_digest_runs')->where('user_id',$user->id)
                    ->where('frequency',$frequency)->where('period_date',$periodDate)
                    ->update(['status'=>'sent','notification_count'=>$unread->count(),'sent_at'=>now(),'updated_at'=>now()]);
                $sent++;
            } catch (\Throwable $error) {
                DB::table('notification_digest_runs')->where('user_id',$user->id)
                    ->where('frequency',$frequency)->where('period_date',$periodDate)
                    ->update(['status'=>'failed','failure_reason'=>mb_substr($error->getMessage(),0,1000),'updated_at'=>now()]);
                report($error);
            }
        }
        $this->info('Digest emails sent: '.$sent);
        return self::SUCCESS;
    }
}
