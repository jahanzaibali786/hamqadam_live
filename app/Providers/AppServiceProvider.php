<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        if (function_exists('get_setting')) {
            $pusherKey = trim((string) get_setting('pusher_app_key', env('PUSHER_APP_KEY')));
            $pusherSecret = trim((string) get_setting('pusher_app_secret', env('PUSHER_APP_SECRET')));
            $pusherAppId = trim((string) get_setting('pusher_app_id', env('PUSHER_APP_ID')));
            $pusherCluster = trim((string) get_setting('pusher_app_cluster', env('PUSHER_APP_CLUSTER')));
            $pusherHost = trim((string) get_setting('pusher_host', env('PUSHER_HOST')));
            $pusherPort = (int) get_setting('pusher_port', env('PUSHER_PORT', 443));
            $pusherScheme = trim((string) get_setting('pusher_scheme', env('PUSHER_SCHEME', 'https')));
            $pusherConfigured = get_setting('chat_realtime_enabled') == 1
                && $pusherKey !== ''
                && $pusherSecret !== ''
                && $pusherAppId !== '';

            $pusherOptions = [
                'cluster' => $pusherCluster,
                'port' => $pusherPort,
                'scheme' => $pusherScheme !== '' ? $pusherScheme : 'https',
                'useTLS' => ($pusherScheme !== '' ? $pusherScheme : 'https') !== 'http',
            ];

            if ($pusherHost !== '' && ! str_starts_with($pusherHost, 'ws-') && ! str_ends_with($pusherHost, 'pusher.com')) {
                $pusherOptions['host'] = $pusherHost;
            }

            config([
                'broadcasting.default' => $pusherConfigured ? 'pusher' : 'log',
                'broadcasting.connections.pusher.key' => $pusherKey,
                'broadcasting.connections.pusher.secret' => $pusherSecret,
                'broadcasting.connections.pusher.app_id' => $pusherAppId,
                'broadcasting.connections.pusher.options' => $pusherOptions,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
