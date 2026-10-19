<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Same-user progression with chronological event evidence; not inferred conversions. */
final class OrderedFunnelService
{
    public function build(int $days = 90): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('analytics_events')) return [];
        $days = max(7, min(365, $days));
        $since = now()->subDays($days);
        $stages = [
            ['label'=>'Discovery','events'=>['discovery_viewed','discovery_opened','search_performed','profile_viewed']],
            ['label'=>'Interest','events'=>['interest_sent','priority_interest_sent']],
            ['label'=>'Match','events'=>['match_created']],
            ['label'=>'Communication','events'=>['chat_started','message_sent','call_started']],
            ['label'=>'Got Match reported','events'=>['got_match_confirmed']],
        ];
        $registered = DB::table('users')->where('user_type','member')->where('created_at','>=',$since)->pluck('id')->all();
        $previous = count($registered);
        $eligible = array_fill_keys($registered, $since->copy()->subSecond()->toDateTimeString());
        $rows = [['label'=>'Registered','users'=>$previous,'conversion_pct'=>null]];
        foreach ($stages as $stage) {
            if ($eligible === []) {
                $rows[] = ['label'=>$stage['label'],'users'=>0,'conversion_pct'=>null];
                continue;
            }
            $next = [];
            // Earliest event strictly following the previous stage for each member.
            foreach (array_chunk(array_keys($eligible), 500) as $ids) {
                $events = DB::table('analytics_events')->whereIn('user_id',$ids)
                    ->whereIn('event_name',$stage['events'])->where('occurred_at','>=',$since)
                    ->orderBy('occurred_at')->get(['user_id','occurred_at']);
                foreach ($events as $event) {
                    if (! isset($next[$event->user_id]) && $event->occurred_at > $eligible[$event->user_id]) {
                        $next[$event->user_id] = $event->occurred_at;
                    }
                }
            }
            $count = count($next);
            $rows[] = ['label'=>$stage['label'],'users'=>$count,
                'conversion_pct'=>$previous ? round($count * 100 / $previous,1) : null];
            $eligible = $next;
            $previous = $count;
        }
        return $rows;
    }
}
