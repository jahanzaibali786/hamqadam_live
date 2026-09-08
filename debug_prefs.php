<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::with(['member','partner_expectations','spiritual_backgrounds','lifestyles'])
    ->where('approved', 1)
    ->whereHas('partner_expectations')
    ->first();

echo "User: {$user->id} ({$user->email})\n";
echo "Has partner_expectations relation: " . ($user->relationLoaded('partner_expectations') ? 'YES' : 'NO') . "\n";
echo "partner_expectations object: " . ($user->partner_expectations ? 'present' : 'null') . "\n";

if ($user->partner_expectations) {
    $p = $user->partner_expectations;
    echo "\n--- All partner_expectations fields ---\n";
    $cols = DB::getSchemaBuilder()->getColumnListing('partner_expectations');
    foreach ($cols as $col) {
        $val = $p->$col;
        $type = gettype($val);
        echo "$col ($type): ";
        if (is_string($val)) echo "'$val'";
        elseif (is_bool($val)) echo $val ? 'true' : 'false';
        elseif (is_null($val)) echo 'NULL';
        elseif (is_array($val)) echo json_encode($val);
        else echo "$val";
        echo "\n";
    }
}
