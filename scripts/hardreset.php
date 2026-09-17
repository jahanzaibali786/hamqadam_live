<?php

/**
 * HARD RESET — production cache purge (web-callable, key protected).
 *
 * Why this exists: deploys happen via FTP mirror which never touches
 * bootstrap/cache/*.php, and PHP opcache can keep serving STALE compiled
 * versions of just-uploaded files. That combination produces boot-level
 * failures on every route ("Target class [view] does not exist", 500s) even
 * when the uploaded code is healthy.
 *
 * What it does:
 *   1. Deletes bootstrap/cache/*.php (config, routes, services, packages, compiled)
 *   2. Clears storage/framework/{cache,views} compiled files
 *   3. Warms the service container fresh (boots the console kernel)
 *   4. Resets PHP opcache if available
 *   5. Prints a health check of key routes
 *
 * Usage (one-time, from the server's htdocs root):
 *   https://hamqadam.com/hardreset.php?key=<CACHE_RESET_KEY>
 *
 * The key must match the CACHE_RESET_KEY GitHub secret used by the deploy
 * workflow. If you did not set that secret yet, set it to the same value you
 * edit into this file's $EXPECTED_KEY below. DELETE this line's real key and
 * keep it long & random — do not commit a weak one.
 *
 * NOTE: this file lives at the DOCROOT ROOT (next to index.php), not in
 * public/, so it is reachable as /hardreset.php on cPanel deployments where
 * the domain points at the project root.
 */

$EXPECTED_KEY = 'CHANGE_ME_TO_A_LONG_RANDOM_STRING'; // keep in sync with GitHub secret CACHE_RESET_KEY

header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if (!hash_equals($EXPECTED_KEY, (string) $key)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

@set_time_limit(120);
echo "== Hamqadam hard reset ==\n";
echo 'time: ' . date('c') . "\n";
echo 'php: ' . PHP_VERSION . "\n\n";

$root = __DIR__;

// 1. Purge compiled bootstrap caches ---------------------------------------
$bootstrapCache = $root . '/bootstrap/cache';
if (!is_dir($bootstrapCache)) {
    @mkdir($bootstrapCache, 0755, true);
    echo "created missing bootstrap/cache\n";
}
$purged = 0;
foreach (glob($bootstrapCache . '/*.php') ?: [] as $file) {
    if (@unlink($file)) {
        $purged++;
    }
}
echo "bootstrap/cache purged: {$purged} file(s)\n";

// 2. Purge compiled views + data cache --------------------------------------
$purged = 0;
foreach ([
    $root . '/storage/framework/views',
    $root . '/storage/framework/cache/data',
] as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isFile()) {
            $purged += @unlink($item->getPathname()) ? 1 : 0;
        } elseif ($item->isDir()) {
            @rmdir($item->getPathname());
        }
    }
}
echo "framework views/cache purged: {$purged} file(s)\n";

// 3. Reset opcache ----------------------------------------------------------
if (function_exists('opcache_reset')) {
    echo 'opcache: ' . (opcache_reset() ? 'reset' : 'reset FAILED') . "\n";
} else {
    echo "opcache: not available (shared hosting often disables it for CLI/web resets)\n";
}

// 4. Warm the container fresh ------------------------------------------------
echo "\n== warming container ==\n";
try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo 'laravel: ' . $app->version() . " — container booted OK\n";
    echo 'env: ' . $app->environment() . "\n";
} catch (Throwable $e) {
    echo 'BOOT FAILED: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo "Fix .env/composer state, then call this script again.\n";
    http_response_code(500);
    exit;
}

// 5. Health snapshot ---------------------------------------------------------
echo "\n== health checks (HTTP) ==\n";
$checks = [
    'web home'  => 'https://hamqadam.com/',
    'api plans' => 'https://hamqadam.com/api/v1/payments/plans',
];
foreach ($checks as $label => $url) {
    $code = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_NOBODY         => false,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    echo str_pad($label, 12) . ": HTTP {$code}\n";
}

echo "\nDone. If any health check is 500, check storage/logs/laravel.log on the server.\n";
