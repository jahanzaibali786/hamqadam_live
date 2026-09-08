<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Api\V1\Matching\MatchmakingIntegrationService;
use Illuminate\Support\Facades\Http;

echo "=== End-to-end test: Backend -> Sidecar ===\n";

// Use user 4 as logged-in (has full preferences) and user 5 as candidate
$user = User::with(['member','partner_expectations','spiritual_backgrounds','lifestyles','career','education','addresses','physical_attributes'])
    ->where('approved', 1)
    ->whereKey(4)
    ->first();

if (!$user) { echo "User 4 not found\n"; exit(1); }
echo "Logged-in user: {$user->id} ({$user->email})\n";

$other = User::with(['member','partner_expectations','spiritual_backgrounds','lifestyles','career','education','addresses','physical_attributes'])
    ->where('approved', 1)
    ->whereKey(5)
    ->first();

if (!$other) { echo "No second user\n"; exit(1); }
echo "Candidate user: {$other->id} ({$other->email})\n";

$service = new MatchmakingIntegrationService();
$ref = new ReflectionClass($service);
$method = $ref->getMethod('toModelProfile');
$method->setAccessible(true);

$profile = $method->invoke($service, $user);
$otherProfile = $method->invoke($service, $other);echo "\n--- Logged-in user has partner_expectations in DB: " . ($user->partner_expectations ? 'YES' : 'NO') . " ---\n";
echo "\n--- Logged-in user profile ---\n";
echo json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "\n--- Logged-in user toModelPreferences ---\n";
$prefsMethod = $ref->getMethod('toModelPreferences');
$prefsMethod->setAccessible(true);
$Prefs = $prefsMethod->invoke($service, $user);
echo json_encode($Prefs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "\n--- Candidate profile ---\n";
echo json_encode($otherProfile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Send both to sidecar
$payload = ['users' => [$profile, $otherProfile]];
echo "\n--- POST /users ---\n";
$response = Http::timeout(10)->post('http://127.0.0.1:8001/users', $payload);
echo "Status: " . $response->status() . "\n";
if (!$response->successful()) {
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

// Get matches
echo "\n--- GET /match/{$profile['user_id']} ---\n";
$matchRes = Http::timeout(10)->get("http://127.0.0.1:8001/match/{$profile['user_id']}");
echo "Status: " . $matchRes->status() . "\n";
$matchBody = json_decode($matchRes->body(), true);
echo json_encode($matchBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if ($matchBody['success'] ?? false) {
    echo "\n=== SUCCESS ===\n";
    echo "Model version: {$matchBody['model_version']}\n";
    echo "Total users evaluated: {$matchBody['total_users_evaluated']}\n";
    echo "Total matches: {$matchBody['total_matches']}\n";
    foreach ($matchBody['matches'] as $m) {
        echo "Match: candidate={$m['candidate_id']} ({\$m['candidate_name']})\n";
        echo "  Score: {$m['match_score']}%, Level: {$m['compatibility_level']}, Status: {$m['match_status']}\n";
        echo "  Confidence: {$m['confidence_score']} ({$m['model_confidence']})\n";
        echo "  Reasons: " . implode('; ', $m['match_reasons'] ?? []) . "\n";
    }
} else {
    echo "\n=== Result: no matches ===\n";
    echo "Error: " . ($matchBody['error'] ?? 'unknown') . "\n";
}

echo "\nDone.\n";
