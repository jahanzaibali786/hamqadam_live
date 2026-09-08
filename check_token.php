<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tok = DB::table('personal_access_tokens')->where('id', 19)->first();
echo "Token ID: 19\n";
echo "Token value: " . $tok->token . "\n";
echo "Token length: " . strlen($tok->token) . "\n";
echo "Name: " . $tok->name . "\n";
echo "Abilities: " . ($tok->abilities ?? 'null') . "\n";
echo "Last used: " . ($tok->last_used_at ?? 'never') . "\n";
