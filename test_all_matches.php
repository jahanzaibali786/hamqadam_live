<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Api\V1\Matching\MatchmakingIntegrationService;
use Illuminate\Support\Facades\Http;

echo "=== Fetching ALL matches for user 4 (demo.user@hamqadam.test) ===\n\n";

// Use the sidecar directly to get the full ranked list
$user = User::with(['member','partner_expectations','spiritual_backgrounds','lifestyles','career','education','addresses','physical_attributes'])
    ->where('approved', 1)
    ->whereKey(4)
    ->first();

if (!$user) {
    echo "User 4 not found\n";
    exit(1);
}
echo "Logged-in: User #{$user->id} ({$user->email})\n";
echo "Gender: " . ($user->member?->gender == '2' ? 'female' : 'male') . "\n";
echo "Age: " . ($user->member?->birthday ? (int) now()->parse($user->member->birthday)->age : 'N/A') . "\n";
echo "Has partner_expectations: " . ($user->partner_expectations ? 'YES' : 'NO') . "\n\n";

// Get all candidates (same logic as MatchRecommendationService)
$ignoredIds = \App\Models\IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')
    ->merge(\App\Models\IgnoredUser::where('user_id', $user->id)->pluck('ignored_by'))
    ->unique()
    ->values();

$candidates = User::query()
    ->with(['member.annualSalaryRange','addresses','education','career','lifestyles','spiritual_backgrounds','partner_expectations'])
    ->where('user_type', 'member')
    ->whereKeyNot($user->id)
    ->where('blocked', 0)
    ->where('deactivated', 0)
    ->where('approved', 1)
    ->whereNotIn('id', $ignoredIds)
    ->whereHas('member', fn($q) => $q->where('hide_profile', 0))
    ->limit(250)
    ->get();

echo "Total eligible candidates: {$candidates->count()}\n\n";

// Call sidecar
$sidecar = new MatchmakingIntegrationService();
$ref = new ReflectionClass($sidecar);
$method = $ref->getMethod('toModelProfile');
$method->setAccessible(true);

$profile = $method->invoke($sidecar, $user);
$candidateProfiles = array_map(fn(User $c) => $method->invoke($sidecar, $c), $candidates->all());

$payload = ['users' => array_merge([$profile], $candidateProfiles)];

echo "Pushing " . count($payload['users']) . " users to sidecar...\n";
$response = Http::timeout(30)->post('http://127.0.0.1:8001/users', $payload);
echo "Push status: " . $response->status() . "\n";

if (!$response->successful()) {
    echo "Sidecar push failed: " . $response->body() . "\n";
    exit(1);
}

echo "Fetching matches for user #{$profile['user_id']}...\n";
$matchRes = Http::timeout(30)->get("http://127.0.0.1:8001/match/{$profile['user_id']}");
echo "Match status: " . $matchRes->status() . "\n";

if (!$matchRes->successful()) {
    echo "Match fetch failed: " . $matchRes->body() . "\n";
    exit(1);
}

$body = $matchRes->json();
echo "\nModel version: {$body['model_version']}\n";
echo "Total users evaluated: {$body['total_users_evaluated']}\n";
echo "Total matches returned: {$body['total_matches']}\n\n";

$matches = $body['matches'] ?? [];

// Sort by match_score descending (highest first)
usort($matches, fn($a, $b) => ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0));

if (empty($matches)) {
    echo "No matches found from sidecar.\n";
} else {
    echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
    echo "│ ALL MATCHES (sorted by score, highest first)                               │\n";
    echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
    foreach ($matches as $i => $m) {
        $score = $m['match_score'] ?? 0;
        $level = $m['compatibility_level'] ?? 'none';
        $status = $m['match_status'] ?? 'unknown';
        $candId = $m['candidate_id'] ?? '???';
        $confidence = $m['confidence_score'] ?? 0;
        $confLabel = $m['model_confidence'] ?? 'low';
        $reasons = implode(' | ', array_slice($m['match_reasons'] ?? [], 0, 3));
        $concerns = implode(' | ', array_slice($m['concerns'] ?? [], 0, 2));
        $recs = implode(' | ', array_slice($m['recommendations'] ?? [], 0, 2));

        echo sprintf(
            "│ #%d  %s  score=%3d%%  level=%s  status=%s  confidence=%d (%s) │\n",
            $i + 1,
            str_pad("candidate#$candId", 12),
            $score,
            str_pad($level, 8),
            str_pad($status, 10),
            $confidence,
            $confLabel
        );

        if ($reasons) {
            echo "│    reasons: {$reasons}                                                 │\n";
        }
        if ($concerns) {
            echo "│    concerns: {$concerns}                                               │\n";
        }
        if ($recs) {
            echo "│    recommendations: {$recs}                                            │\n";
        }
        echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
    }
}

echo "\nDone.\n";
