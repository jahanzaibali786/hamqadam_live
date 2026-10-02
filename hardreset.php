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
 *   5. Reports storage/ writability + pending migrations
 *   6. HTTP health checks
 *
 * Migrations are NOT run by default, because an FTP mirror that only uploads
 * code leaves the database behind and every new column is then a 500. Pass
 * ?migrate=1 (or set AUTO_MIGRATE=1 in .env) to run `migrate --force` as part
 * of the reset. The pending count is always printed so the deploy log shows
 * it either way.
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

// Check writable runtime directories before purging any caches.
//
// An unwritable directory is reported, not fatal: the app now falls back to
// stores that do not need the filesystem (see AppServiceProvider), so the site
// keeps serving while the host is still misconfigured. Bailing out here would
// only hide the rest of this report — including the migration list.
$unwritable = [];
foreach ([
    'bootstrap/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
] as $relativeDirectory) {
    $directory = $root . '/' . $relativeDirectory;
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        $unwritable[] = $relativeDirectory;
        continue;
    }
    if (!is_writable($directory)) {
        $unwritable[] = $relativeDirectory;
    }
}

if ($unwritable) {
    echo "\n!! NOT WRITABLE by the PHP worker:\n";
    foreach ($unwritable as $dir) {
        echo "!!   {$dir}\n";
    }
    echo "!! Fix on the host:\n";
    echo "!!   chown -R <php-user>:<php-user> storage bootstrap/cache && chmod -R 775 storage bootstrap/cache\n";
}

// 1. Purge compiled bootstrap caches ----------------------------------------
$bootstrapCache = $root . '/bootstrap/cache';
if (!is_dir($bootstrapCache)) {
    @mkdir($bootstrapCache, 0755, true);
    echo "created missing bootstrap/cache\n";
}
$purged = 0;
$skipped = [];
if (!is_dir($bootstrapCache) || !is_writable($bootstrapCache)) {
    // Deleting what we cannot rewrite would strand the site: PackageManifest
    // and ProviderRepository both rebuild into this directory during bootstrap
    // and throw when they cannot, so every later request 500s with no way back.
    echo "bootstrap/cache is not writable — skipping the purge so the package\n";
    echo "manifest survives. Fix the host permissions, then deploy again.\n";
} else {
    foreach (glob($bootstrapCache . '/*.php') ?: [] as $file) {
        $name = basename($file);
        // packages.php + services.php are the package manifest and the
        // compiled provider list. They only need rebuilding after a composer
        // change, and removing them is what turns a permissions problem into a
        // permanent outage — so they are left alone.
        if (in_array($name, ['packages.php', 'services.php'], true)) {
            $skipped[] = $name;
            continue;
        }
        if (@unlink($file)) {
            $purged++;
        }
    }
}
echo 'bootstrap/cache purged: ' . $purged . ' file(s)';
echo $skipped ? ' (kept: ' . implode(', ', $skipped) . ')' : '';
echo "\n";

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
        }
        // Preserve directories: live cache writers may already have checked them.
    }
}
echo "framework views/cache purged: {$purged} file(s)\n";

// 2b. Make sure the writable directories exist ------------------------------
// A missing directory and a wrongly-owned one look identical from the app's
// side: file_put_contents() fails with "Permission denied" either way.
echo "\n== storage ==\n";
foreach ([
    'bootstrap/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'storage/app/public',
] as $relative) {
    $dir = $root . '/' . $relative;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        echo str_pad($relative, 30) . ": COULD NOT CREATE\n";
        continue;
    }
    echo str_pad($relative, 30) . ': ' . (is_writable($dir) ? 'writable' : 'NOT WRITABLE') . "\n";
}
if (!is_writable($root . '/storage/framework/cache/data')) {
    echo "\n!! storage/ is not writable by PHP.\n";
    echo "!! Every route that caches will 500 with 'file_put_contents(...): Permission denied'.\n";
    echo "!! Fix on the host:  chown -R <php-user>:<php-user> storage bootstrap/cache && chmod -R 775 storage bootstrap/cache\n";
}

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

// 5. Migrations --------------------------------------------------------------
// The FTP deploy uploads code but never touches the database, so code that
// needs a new column/table is live before the schema is. Report the count
// every time; only apply when asked.
echo "\n== migrations ==\n";
$shouldMigrate = ($_GET['migrate'] ?? '') === '1'
    || hardreset_env('AUTO_MIGRATE') === '1';

try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $migrator = $app->make(Illuminate\Database\Migrations\Migrator::class);
    $pending = array_keys($migrator->getMigrationFiles(
        $app->databasePath('migrations')
    ));
    $ran = $migrator->getRepository()->getRan();

    $pending = array_values(array_diff($pending, $ran));

    echo 'pending: ' . count($pending) . "\n";
    foreach (array_slice($pending, 0, 40) as $migration) {
        echo "  - {$migration}\n";
    }
    if (count($pending) > 40) {
        echo '  ... and ' . (count($pending) - 40) . " more\n";
    }

    if ($shouldMigrate && $pending) {
        echo "\nrunning migrate --force ...\n";
        $kernel->call('migrate', ['--force' => true, '--no-interaction' => true]);
        echo trim($kernel->output()) . "\n";
    } elseif ($pending) {
        echo "(not applied — re-call with ?migrate=1)\n";
    }
} catch (Throwable $e) {
    echo 'MIGRATION CHECK FAILED: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

// 6. Health checks ------------------------------------------------------------
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
