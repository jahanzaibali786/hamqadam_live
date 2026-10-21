<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Actual D1/D7/D30 observed activity, not estimates from last-login timestamps.
 * A cohort remains "not mature" until members have had the relevant observation window.
 */
final class RetentionCohortService
{
    public function monthly(int $months = 6): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('analytics_events') ||
            DB::connection()->getDriverName() !== 'mysql') return [];
        $months = max(1, min(12, $months));
        $first = now()->startOfMonth()->subMonths($months - 1);
        $sql = <<<'SQL'
            SELECT DATE_FORMAT(u.created_at, '%Y-%m') AS cohort,
              COUNT(DISTINCT CASE WHEN e.occurred_at >= DATE_ADD(u.created_at, INTERVAL 1 DAY)
                  AND e.occurred_at < DATE_ADD(u.created_at, INTERVAL 2 DAY) AND u.created_at <= DATE_SUB(NOW(), INTERVAL 2 DAY) THEN u.id END) AS d1_active,
              COUNT(DISTINCT CASE WHEN e.occurred_at >= DATE_ADD(u.created_at, INTERVAL 7 DAY)
                  AND e.occurred_at < DATE_ADD(u.created_at, INTERVAL 8 DAY) AND u.created_at <= DATE_SUB(NOW(), INTERVAL 8 DAY) THEN u.id END) AS d7_active,
              COUNT(DISTINCT CASE WHEN e.occurred_at >= DATE_ADD(u.created_at, INTERVAL 30 DAY)
                  AND e.occurred_at < DATE_ADD(u.created_at, INTERVAL 31 DAY) AND u.created_at <= DATE_SUB(NOW(), INTERVAL 31 DAY) THEN u.id END) AS d30_active
            FROM users u LEFT JOIN analytics_events e ON e.user_id = u.id
            WHERE u.user_type = 'member' AND u.created_at >= ?
            GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
        SQL;
        $observed = collect(DB::select($sql, [$first->toDateTimeString()]))->keyBy('cohort');
        $rows = [];
        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = now()->startOfMonth()->subMonths($offset);
            $end = $month->copy()->endOfMonth();
            $members = DB::table('users')->where('user_type', 'member')
                ->whereBetween('created_at', [$month, $end]);
            $row = ['month' => $month->format('M Y'), 'signups' => (clone $members)->count()];
            $result = $observed->get($month->format('Y-m'));
            foreach ([1,7,30] as $day) {
                $mature = (clone $members)->where('created_at','<=',now()->subDays($day + 1))->count();
                $observations = (int) ($result->{'d'.$day.'_active'} ?? 0);
                $row['d'.$day] = $mature ? round(100 * min($observations, $mature) / $mature, 1) : null;
                $row['d'.$day.'_eligible'] = $mature;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
