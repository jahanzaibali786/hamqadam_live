<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Records the member's chat presence: the last moment they actively used the
 * app. Read by the chat resources so the other side can show "Online" /
 * "Last seen 5 minutes ago" under the conversation header.
 *
 * Runs on every authenticated /api/v1 request. Writing on every single request
 * would be an UPDATE storm (the app itself is chatty: typing pings, fallback
 * polls, push registration), so the write is throttled to at most once a
 * minute per member — far more precision than a "5 minutes ago" label needs.
 */
class EnsureApiMemberActivity
{
    /**
     * How fresh "last_active_at" must be before the middleware skips the write.
     * Also the threshold below which the app shows "Online" — kept in sync by
     * convention: a member active within 2 minutes is effectively online.
     */
    public const ONLINE_WINDOW_SECONDS = 120;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user !== null) {
            try {
                // One conditional UPDATE: writes only when the stamp is stale,
                // so a burst of requests in the same minute costs nothing.
                $affected = DB::table('users')
                    ->where('id', $user->id)
                    ->where(function ($query): void {
                        $query->whereNull('last_active_at')
                            ->orWhere('last_active_at', '<', now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
                    })
                    ->update(['last_active_at' => now()]);

                // Keep the loaded model in step so the same request's response
                // (and anything reading $user->last_active_at) is accurate.
                if ($affected > 0) {
                    $user->last_active_at = now();
                }
            } catch (\Throwable $e) {
                // Presence is best-effort: a failed stamp must never fail the
                // API call it rode on.
            }
        }

        return $next($request);
    }
}
