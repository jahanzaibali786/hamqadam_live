<?php
/**
 * Hamqadam deployment repair for Linux/PSR-4 case-sensitive paths.
 * Source inspected at main commit b7d2fe81cf20f66dbc112e3bdb695095ba76b325.
 * Run from the Laravel project root: php apply_instamojo_route_fix.php
 */
declare(strict_types=1);

$root = getcwd();
if (!is_file($root.'/artisan') || !is_file($root.'/composer.json')) {
    fwrite(STDERR, "ERROR: Run this from the Laravel application root (where artisan lives).\n");
    exit(1);
}

$fixes = [
    'routes/api.php' => [
        'use App\\Http\\Controllers\\Api\\payment\\InstamojoController;'
            => 'use App\\Http\\Controllers\\Api\\Payment\\InstamojoController;',
    ],
    'app/Http/Controllers/Api/Payment/InstamojoController.php' => [
        'use App\\Http\\Controllers\\APi\\WalletController;'
            => 'use App\\Http\\Controllers\\Api\\WalletController;',
    ],
];

foreach ($fixes as $relative => $replacements) {
    $path = $root.'/'.$relative;
    if (!is_file($path)) {
        fwrite(STDERR, "ERROR: Missing file: {$relative}\n");
        exit(1);
    }
    $source = file_get_contents($path);
    if ($source === false) {
        fwrite(STDERR, "ERROR: Cannot read {$relative}\n");
        exit(1);
    }
    $updated = $source;
    foreach ($replacements as $before => $after) {
        if (str_contains($updated, $after)) {
            echo "Already correct: {$relative}\n";
            continue;
        }
        if (!str_contains($updated, $before)) {
            fwrite(STDERR, "ERROR: Expected import missing in {$relative}; not changing unfamiliar code.\n");
            exit(1);
        }
        $updated = str_replace($before, $after, $updated);
    }
    if ($updated !== $source) {
        $backup = sys_get_temp_dir().'/hamqadam-'.str_replace(['/','\\'], '-', $relative).'-'.date('YmdHis').'.bak';
        if (file_put_contents($backup, $source, LOCK_EX) === false) {
            fwrite(STDERR, "ERROR: Could not back up {$relative}\n");
            exit(1);
        }
        if (file_put_contents($path, $updated, LOCK_EX) === false) {
            fwrite(STDERR, "ERROR: Could not update {$relative}. Backup: {$backup}\n");
            exit(1);
        }
        echo "Fixed: {$relative} (backup: {$backup})\n";
    }
}
echo "Completed. Run php -l on both changed files, then composer dump-autoload -o and php artisan route:list.\n";
