<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class NotificationEngagementReportService {
    public function summarize(int $days=30): array {
        if(!Schema::hasTable('notification_delivery_logs'))return [];
        $days=max(1,min(365,$days));
        return DB::table('notification_delivery_logs')->where('created_at','>=',now()->subDays($days))
            ->select('channel','event_key')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('SUM(CASE WHEN sent_at IS NOT NULL THEN 1 ELSE 0 END) as sent')
            ->selectRaw('SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as delivered_confirmations')
            ->selectRaw('SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_events')
            ->selectRaw('SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicks')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failures',['failed'])
            ->selectRaw('SUM(COALESCE(reminder_count, 0)) as reminders')
            ->groupBy('channel','event_key')->orderByDesc('attempts')->limit(30)->get()->map(fn($r)=>[
                'channel'=>$r->channel,'event'=>$r->event_key,'attempts'=>(int)$r->attempts,
                'sent'=>(int)$r->sent,'delivered'=>(int)$r->delivered_confirmations,
                'reads'=>(int)$r->read_events,'clicks'=>(int)$r->clicks,'failures'=>(int)$r->failures,
                'reminders'=>(int)$r->reminders,'read_rate'=>(int)$r->sent>0?round(100*$r->reads/$r->sent,1):null
            ])->all();
    }
}
