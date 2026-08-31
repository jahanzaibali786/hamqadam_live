<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Register a single /broadcasting/auth route that handles both:
     *
     *  1. **Website (Echo)** — authenticates via session cookie (web middleware).
     *  2. **Mobile app** — authenticates via Sanctum Bearer token.
     *
     * Laravel's default `Broadcast::routes()` only registers with `web` + `auth`
     * middleware. Mobile apps send a Bearer header, so they always get 403 —
     * which silently kills all realtime (no chat messages, no call signals).
     *
     * This custom route tries session auth first; if the user is not
     * authenticated via session, it falls back to Sanctum token auth.
     * Both paths end up calling `Broadcast::auth()` with a fully
     * authenticated `$request`.
     */
    public function boot(): void
    {
        // Remove the default Broadcast::routes() call and replace with
        // a single route that handles both web and API auth.
        Route::post('/broadcasting/auth', function (Request $request) {
            // If the request already has a user (e.g. sanctum middleware
            // was applied by a parent group), just authorize.
            if ($request->user()) {
                return Broadcast::auth($request);
            }

            // Try session/web auth (for the website)
            if (auth()->check()) {
                return Broadcast::auth($request);
            }

            // Try Sanctum bearer token (for the mobile app)
            $token = $request->bearerToken();
            if ($token) {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
                if ($user) {
                    \Illuminate\Support\Facades\Auth::setUser($user);
                    return Broadcast::auth($request);
                }
            }

            return response()->json(['error' => 'Unauthenticated'], 401);
        });

        require base_path('routes/channels.php');
    }
}
