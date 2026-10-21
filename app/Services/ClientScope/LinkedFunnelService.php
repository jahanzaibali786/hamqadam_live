<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** User-level progressive cohorts; only observed rows can advance a stage. */
final class LinkedFunnelService
{
    public function build(int $days = 90): array
    {
        $days = max(7, min(365, $days));
        if (! Schema::hasTable('users') || ! Schema::hasTable('analytics_events')) return [];
        $since = now()->subDays($days);
        $base = DB::table('users')->where('user_type', 'member')
            ->where('created_at', '>=', $since)->pluck('id')->map(fn ($id) => (int)$id)->all();
        $stages = [
            ['name'=>'Registered', 'events'=>[]],
            ['name'=>'Profile completed', 'events'=>['profile_completed']],
            ['name'=>'Discovery', 'events'=>['discovery_viewed','discovery_opened','search_performed','profile_viewed']],
            ['name'=>'Interest', 'events'=>['interest_sent','priority_interest_sent']],
            ['name'=>'Match', 'events'=>['match_created']],
            ['name'=>'Communication', 'events'=>['chat_started','message_sent','call_started']],
            ['name'=>'Got Match confirmed', 'events'=>['got_match_confirmed']],
        ];
        $progressive = array_fill_keys($base, true);
        $rows = [];
        $previous = count($base);
        foreach ($stages as $step) {
            if ($step['events']) {
                $ids = DB::table('analytics_events')->whereIn('event_name', $step['events'])
                    ->whereNotNull('user_id')->where('occurred_at','>=',$since)
                    ->whereIn('user_id', array_keys($progressive))->distinct()
                    ->pluck('user_id')->map(fn ($id)=>(int)$id)->all();
                $progressive = array_fill_keys($ids, true);
            }
            $count = count($progressive);
            $rows[] = [
                'name'=>$step['name'],
                'users'=>$count,
                'conversion_pct'=>$previous ? round(100*$count/$previous,1) : null,
                'dropoff'=>$previous-$count,
                'basis'=>'Linked registered users with observed stage events',
            ];
            $previous=$count;
        }
        return $rows;
    }
}
