<?php

/**
 * HARD RESET — production cache purge (web-callable, key protected).
 *
 * Why this exists: deploys happen via FTP mirror that never touches
 * bootstrap/cache/*.php, and opcache can serve stale compiled files.
 * That combination breaks boot on every route ("Target class [view]
 * does not exist", 500s) even when the uploaded code is healthy.
 *
 * What it does:
 *   1. Deletes bootstrap/cache/*.php (config, routes, services, packages)
 *   2. Clears storage/framework/views + cache compiled files
 *   3. Resets PHP opcache when available
 *   4. Warms the service container fresh
 *   5. HTTP health checks
 *
 * Setup (one time):
 *   1. Put this file at the DOCROOT ROOT next to index.php (hamqadam.com/hardreset.php)
 *   2. Add to the server's .env:
 *        CACHE_RESET_KEY=<a long random string>
 *   3. Add the SAME value as the GitHub secret CACHE_RESET_KEY
 *   4. Call once: https://hamqadam.com/hardreset.php?key=...  (deploys call it automatically)
 *
 * To disable: remove the script from the server or remove the .env key.
 */

function hardreset_env(string $key): ?string
{
    $path = __DIR__ . '/.env';
    if (!is_readable($path)) {
        return null;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        if (trim($k) === $key) {
            return trim($v, " \t\n\r\0\x0B\"'");
        }
    }

    return null;
}

$root     = __DIR__; // docroot root = Laravel project root here
$expected = hardreset_env('CACHE_RESET_KEY');
$key      = $_GET['key'] ?? '';

if (!$expected || !hash_equals($expected, (string) $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden\n";
    exit;
}

@set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
echo "== Hamqadam hard reset ==\n";
echo 'time: ' . date('c') . "\n";
echo 'php: ' . PHP_VERSION . "\n\n";

// 1. Purge compiled bootstrap caches ----------------------------------------
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

// 2. Purge framework views + data cache --------------------------------------
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

// 3. opcache reset -----------------------------------------------------------
if (function_exists('opcache_reset')) {
    echo 'opcache: ' . (opcache_reset() ? 'reset' : 'reset FAILED') . "\n";
} else {
    echo "opcache: not available (cPanel may not allow web opcache reset)\n";
}

// 4. Warm the container fresh -------------------------------------------------
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

// 5. Health checks ------------------------------------------------------------
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
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    echo str_pad($label, 12) . ": HTTP {$code}\n";
}

echo "\nDone. If any health check is 500, check storage/logs/laravel.log on the server.\n";
