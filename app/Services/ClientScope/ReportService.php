<?php

namespace App\Services\ClientScope;

use App\Models\AnalyticsEvent;
use App\Models\SatisfactionSurvey;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportService
{
    private function countTable(string $table, ?string $column = null): int
    {
        if (! Schema::hasTable($table) || ($column && ! Schema::hasColumn($table, $column))) return 0;
        return $column ? (int) DB::table($table)->distinct()->count($column) : (int) DB::table($table)->count();
    }

    public function build(): array
    {
        $users = $this->countTable('users');
        $funnel = [
            ['label' => 'Registered accounts', 'users' => $users, 'source' => 'users'],
            ['label' => 'Completed registration', 'users' => $this->profileCompleted(), 'source' => 'members.registration_completed_at'],
            ['label' => 'Discovery activity tracked', 'users' => $this->eventUsers(['discovery_opened','profile_viewed','search_performed']), 'source' => 'analytics_events'],
            ['label' => 'Expressed interest', 'users' => $this->countTable('express_interests','interested_by'), 'source' => 'express_interests'],
            ['label' => 'Matched', 'users' => $this->countTable('profile_matches','user_id'), 'source' => 'profile_matches'],
            ['label' => 'Communication tracked', 'users' => $this->eventUsers(['chat_started','message_sent','call_started']), 'source' => 'analytics_events'],
            ['label' => 'Reported Got Match', 'users' => $this->countTable('match_successes','user_id'), 'source' => 'match_successes (user-reported)'],
        ];
        foreach ($funnel as $i => &$row) {
            $preceding = $i === 0 ? 0 : (int) $funnel[$i - 1]['users'];
            $row['relative_to_previous_pct'] = $i === 0 || $preceding === 0 ? null : round(100 * $row['users'] / $preceding, 1);
        }
        unset($row);

        $nps = ['responses' => 0, 'promoters' => 0, 'passives' => 0, 'detractors' => 0, 'score' => null];
        if (Schema::hasTable('satisfaction_surveys')) {
            $scores = SatisfactionSurvey::query()->where('context', 'nps')->selectRaw('score, COUNT(*) as total')->groupBy('score')->pluck('total', 'score');
            foreach ($scores as $score => $count) {
                $nps['responses'] += (int) $count;
                if ((int) $score >= 9) $nps['promoters'] += (int) $count;
                elseif ((int) $score >= 7) $nps['passives'] += (int) $count;
                else $nps['detractors'] += (int) $count;
            }
            if ($nps['responses'] > 0) $nps['score'] = round(100 * ($nps['promoters'] - $nps['detractors']) / $nps['responses'], 1);
        }

        $adoption = Schema::hasTable('analytics_events')
            ? AnalyticsEvent::query()->whereNotNull('feature_key')
                ->selectRaw('feature_key, COUNT(*) as uses, COUNT(DISTINCT user_id) as people')
                ->groupBy('feature_key')->orderByDesc('uses')->limit(30)->get()
            : collect();

        $geography = (Schema::hasTable('addresses') && Schema::hasTable('cities') && Schema::hasColumn('addresses','city_id'))
            ? DB::table('addresses')->join('cities','cities.id','=','addresses.city_id')
                ->selectRaw('cities.name as location, COUNT(DISTINCT addresses.user_id) as users')
                ->groupBy('cities.id','cities.name')->orderByDesc('users')->limit(15)->get()
            : collect();

        $ratings = Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'rating')
            ? DB::table('support_tickets')->whereNotNull('rating')->selectRaw('COUNT(*) as responses, AVG(rating) as average')->first()
            : null;

        return [
            'funnel' => $funnel,
            'linked_funnel' => app(\App\Services\ClientScope\LinkedFunnelService::class)->build(90),
            'ordered_funnel' => app(\App\Services\ClientScope\OrderedFunnelService::class)->build(90),
            'nps' => $nps,
            'nps_trend' => $this->npsTrend(),
            'adoption' => $adoption,
            'geography' => $geography,
            'geo_heatmap' => app(\App\Services\ClientScope\GeographicHeatmapService::class)->report(),
            'notification_engagement' => app(\App\Services\ClientScope\NotificationEngagementReportService::class)->summarize(30),
            'hera_performance' => app(\App\Services\ClientScope\HeraPerformanceReportService::class)->report(),
            'translation_coverage' => app(\App\Services\ClientScope\TranslationCoverageService::class)->report(),
            'verified_outcomes' => Schema::hasTable('match_outcome_reviews') ? (int) DB::table('match_outcome_reviews')->where('status','verified')->count() : 0,
            'verified_stories' => Schema::hasTable('match_outcome_reviews') ? (int) DB::table('match_outcome_reviews')->where('status','verified')->whereNotNull('happy_story_id')->count() : 0,
            'outcome_candidates' => Schema::hasTable('match_outcome_reviews') ? (int) DB::table('match_outcome_reviews')->count() : 0,
            'cohorts' => $this->cohorts(),
            'observed_retention' => app(\App\Services\ClientScope\RetentionCohortService::class)->monthly(),
            'total_matches' => $this->countTable('profile_matches'),
            'got_match_confirmations' => $this->countTable('match_successes'),
            'successful_stories' => $this->countTable('happy_stories'),
            'support_responses' => $ratings?->responses ?? 0,
            'support_average' => $ratings?->average ? round((float) $ratings->average, 2) : null,
        ];
    }

    /** Monthly NPS trend from actual submitted survey records; no synthetic data. */
    private function npsTrend(): array
    {
        if (! Schema::hasTable('satisfaction_surveys')) return [];
        $trend = [];
        for ($monthsBack = 5; $monthsBack >= 0; $monthsBack--) {
            $start = now()->startOfMonth()->subMonths($monthsBack);
            $end = $start->copy()->endOfMonth();
            $result = DB::table('satisfaction_surveys')->where('context','nps')
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COUNT(*) AS responses, SUM(CASE WHEN score >= 9 THEN 1 ELSE 0 END) AS promoters, SUM(CASE WHEN score <= 6 THEN 1 ELSE 0 END) AS detractors')
                ->first();
            $count = (int)($result->responses ?? 0);
            $trend[] = [
                'month' => $start->format('M Y'),
                'responses' => $count,
                'score' => $count ? round(100 * ((int)$result->promoters - (int)$result->detractors) / $count, 1) : null,
            ];
        }
        return $trend;
    }

    private function profileCompleted(): int
    {
        if (! Schema::hasTable('members') || ! Schema::hasColumn('members','registration_completed_at')) return 0;
        return (int) DB::table('members')->whereNotNull('registration_completed_at')->distinct()->count('user_id');
    }

    private function eventUsers(array $events): int
    {
        if (! Schema::hasTable('analytics_events')) return 0;
        return (int) AnalyticsEvent::query()->whereIn('event_name', $events)->whereNotNull('user_id')->distinct()->count('user_id');
    }

    private function cohorts(): array
    {
        if (! Schema::hasTable('users')) return [];
        $result = [];
        for ($monthsBack = 5; $monthsBack >= 0; $monthsBack--) {
            $start = now()->startOfMonth()->subMonths($monthsBack);
            $end = $start->copy()->endOfMonth();
            $count = DB::table('users')->whereBetween('created_at', [$start, $end])->count();
            // Explicitly label observed events; no events ≠ proven zero retention.
            $active = 0;
            if (Schema::hasTable('analytics_events')) {
                $active = DB::table('analytics_events')->join('users','users.id','=','analytics_events.user_id')
                    ->whereBetween('users.created_at', [$start, $end])
                    ->whereBetween('analytics_events.occurred_at', [now()->subDays(30), now()])
                    ->distinct()->count('analytics_events.user_id');
            }
            $result[] = ['month' => $start->format('M Y'), 'signups' => (int) $count, 'active_last_30d' => (int) $active];
        }
        return $result;
    }
}
