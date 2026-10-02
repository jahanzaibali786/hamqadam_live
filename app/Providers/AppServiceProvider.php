<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->keepFileBackedStoresWritable();

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
     * Stop an unwritable storage/ from taking the whole site down.
     *
     * The file cache writes to storage/framework/cache/data and the file
     * session driver writes to storage/framework/sessions. Shared hosts
     * often ship those directories owned by the FTP user while PHP runs as
     * a different user, and the resulting "Permission denied" used to surface
     * as a 500 on every route — including routes that never touch the cache,
     * because AppServiceProvider::boot() reads settings (and therefore
     * writes the cache) before anything else runs.
     *
     * This does NOT fix the host: storage/ still needs the right ownership
     * and permissions. It only keeps the site serving while it is wrong.
     */
    private function keepFileBackedStoresWritable(): void
    {
        if (config('cache.default') === 'file') {
            $this->fallBackWhenUnwritable('cache.default', config('cache.stores.file.path'), 'cache');
        }

        if (config('session.driver') === 'file') {
            $this->fallBackWhenUnwritable('session.driver', config('session.files'), 'sessions');
        }
    }

    /**
     * Point one config key at a store that does not need the filesystem.
     *
     * The database store is preferred when its table exists, since it keeps
     * caching/sessions working across requests; `array` is the last resort
     * because it always works (it just does not persist between requests).
     */
    private function fallBackWhenUnwritable(string $configKey, mixed $path, string $table): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        if (is_dir($path) && is_writable($path)) {
            return;
        }

        $fallback = $this->tableExists($table) ? 'database' : 'array';

        config([$configKey => $fallback]);

        // Writing to storage/logs is exactly the thing that is broken here, so
        // this must not be allowed to throw — it would re-create the very 500
        // this method exists to prevent.
        try {
            Log::warning("The \"{$configKey}\" store is not writable at {$path}; using the \"{$fallback}\" store instead. Fix the ownership and permissions of storage/ on the host.", [
                'php_uid' => function_exists('getmyuid') ? getmyuid() : null,
            ]);
        } catch (\Throwable) {
            // Logging is unavailable on this host too — nothing else to do.
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            // The database may itself be unavailable — stay on the in-memory store.
            return false;
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (class_exists(
            \App\Services\Api\V1\Matching\MatchmakingIntegrationService::class
        )) {
            $this->app->singleton(
                \App\Services\Api\V1\Matching\MatchmakingIntegrationService::class,
                function () {
                    return new \App\Services\Api\V1\Matching\MatchmakingIntegrationService(
                        baseUrl: config('services.matchmaking.base_url', 'https://matchmaking.hamqadam.com'),
                        timeout: (int) config('services.matchmaking.timeout', 30),
                        apiKey: (string) config('services.matchmaking.api_key', ''),
                    );
                }
            );
        }
    }
}
