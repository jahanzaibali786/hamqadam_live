<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Explicit event-based insights; no synthetic users, locations or conversions. */
final class AnalyticsInsightsService
{
    public function summarize(int $days = 30): array
    {
        $days = max(7, min($days, 180));
        $since = now()->subDays($days);
        $available = Schema::hasTable('analytics_events');
        $eventRows = $available ? DB::table('analytics_events')->where('occurred_at','>=',$since)
            ->select('event_name')->selectRaw('COUNT(*) AS events, COUNT(DISTINCT user_id) AS members')
            ->groupBy('event_name')->orderByDesc('events')->limit(30)->get() : collect();
        $events = $eventRows->keyBy('event_name');
        $stages = [
            'registration_completed' => Schema::hasTable('users') ? DB::table('users')->where('user_type','member')
                ->where('created_at','>=',$since)->count() : 0,
            'profile_completed' => (int) ($events->get('profile_completed')?->members ?? 0),
            'discovery_viewed' => (int) ($events->get('discovery_opened')?->members ?? $events->get('discovery_viewed')?->members ?? 0),
            'interest_sent' => (int) ($events->get('interest_sent')?->members ?? 0),
            'match_created' => (int) ($events->get('match_created')?->members ?? 0),
            'message_sent' => (int) ($events->get('message_sent')?->members ?? 0),
            'got_match_confirmed' => (int) ($events->get('got_match_confirmed')?->members ?? 0),
        ];
        $activeUsers = $available ? (int) DB::table('analytics_events')
            ->where('occurred_at','>=',$since)->whereNotNull('user_id')->distinct()->count('user_id') : 0;
        $adoption = $available ? DB::table('analytics_events')->where('occurred_at','>=',$since)
            ->whereNotNull('feature_key')->select('feature_key')
            ->selectRaw('COUNT(*) AS uses, COUNT(DISTINCT user_id) AS members')
            ->groupBy('feature_key')->orderByDesc('uses')->limit(20)->get() : collect();
        $daily = $available ? DB::table('analytics_events')->where('occurred_at','>=',$since)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) AS events, COUNT(DISTINCT user_id) AS members')
            ->groupBy(DB::raw('DATE(occurred_at)'))->orderBy('day')->get() : collect();
        $geography = Schema::hasTable('addresses') && Schema::hasTable('cities')
            ? DB::table('addresses')->join('users','users.id','=','addresses.user_id')
                ->leftJoin('cities','cities.id','=','addresses.city_id')
                ->where('users.user_type','member')->whereNull('users.deleted_at')
                ->whereNotNull('addresses.city_id')
                ->selectRaw('addresses.city_id, MAX(cities.name) AS city, COUNT(DISTINCT users.id) AS members')
                ->groupBy('addresses.city_id')->orderByDesc('members')->limit(18)->get()
            : collect();
        return compact('days','stages','activeUsers','adoption','daily','geography','eventRows');
    }
}
