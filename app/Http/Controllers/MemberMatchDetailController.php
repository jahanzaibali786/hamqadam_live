<?php

namespace App\Http\Controllers;

use App\Models\ProfileMatch;
use App\Models\Shortlist;
use App\Support\MemberMatchCardActions;
use Illuminate\Http\Request;

class MemberMatchDetailController extends Controller
{
    public function show(Request $request, int $member)
    {
        $viewer = $request->user();
        // The matched-profiles listing may display transient AI-ranked results
        // (ProfileSearchService::rankedMatches builds unsaved ProfileMatch objects).
        // The old firstOrFail() required a persisted row and returned 404 even
        // for a visible candidate. Resolve the candidate securely first.
        $person = \App\Models\User::query()
            ->whereKey($member)
            ->where('user_type', 'member')
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', fn ($q) => $q
                ->where(fn ($m) => $m->where('hide_profile', 0)->orWhereNull('hide_profile')))
            ->whereDoesntHave('profile_privacy_setting', fn ($q) => $q->where('invisible_mode', true))
            ->with([
                'member', 'addresses.city', 'education', 'career',
                'spiritual_backgrounds.religion', 'lifestyles', 'physical_attributes',
                'partner_expectations',
            ])
            ->firstOrFail();

        abort_if((int) $viewer->id === (int) $person->id, 404);

        // Hide/invisible/ignored profiles remain inaccessible. These checks
        // are scoped to the two members and cannot leak a third-party profile.
        abort_if(\App\Models\HiddenProfileUser::query()
            ->where('hidden_from_user_id', $viewer->id)
            ->where('user_id', $person->id)->exists(), 404);
        abort_if(\App\Models\IgnoredUser::query()
            ->where(function ($query) use ($viewer, $person) {
                $query->where(fn ($pair) => $pair->where('ignored_by', $viewer->id)->where('user_id', $person->id))
                    ->orWhere(fn ($pair) => $pair->where('ignored_by', $person->id)->where('user_id', $viewer->id));
            })->exists(), 404);

        // Prefer authoritative saved scoring, but don't require it merely to
        // open the explanation. Preview data is ephemeral: no DB match rows
        // are inserted just because a member viewed the explanation.
        $match = ProfileMatch::query()
            ->visibleToMember($viewer)
            ->where('match_id', $person->id)
            ->first();

        if ($match === null) {
            $preview = app(\App\Services\Api\V1\Matching\MatchmakingIntegrationService::class)
                ->compatibilityPreview($viewer, $person);

            $score = ProfileMatch::displayPercentage((int) ($preview['percentage'] ?? 0));
            $match = new ProfileMatch([
                'user_id' => $viewer->id,
                'match_id' => $person->id,
                'match_percentage' => $score ?? 0,
                'score_breakdown' => $preview['breakdown'] ?? [],
                'compatibility_reasons' => $preview['reasons'] ?? [],
                'compatibility_explanation' => $preview['explanation']
                    ?? 'Match insights are unavailable at the moment.',
            ]);
        }
        $match->setRelation('matchedUser', $person);
        $viewer->loadMissing([
            'member', 'addresses.city', 'education', 'career',
            'spiritual_backgrounds', 'lifestyles',
        ]);

        $matchActions = MemberMatchCardActions::between($viewer, $person);
        $isShortlisted = Shortlist::query()
            ->where('shortlisted_by', $viewer->id)
            ->where('user_id', $person->id)
            ->exists();

        // A score breakdown can contain weighted rule matches, normalized
        // percentages from an AI service, or absent factors. Never invent scores.
        $groups = [
            ['label' => 'Religion & Beliefs', 'icon' => 'la-moon', 'keys' => ['religion', 'prayer', 'faith']],
            ['label' => 'Lifestyle & Habits', 'icon' => 'la-home', 'keys' => ['lifestyle', 'diet', 'habits']],
            ['label' => 'Education & Career', 'icon' => 'la-graduation-cap', 'keys' => ['education', 'profession', 'career']],
            ['label' => 'Family Orientation', 'icon' => 'la-users', 'keys' => ['family', 'values']],
            ['label' => 'Personality Match', 'icon' => 'la-user', 'keys' => ['personality', 'behavior', 'emotional', 'communication']],
            ['label' => 'Future Goals', 'icon' => 'la-bullseye', 'keys' => ['long_term', 'future', 'marriage_timeline']],
        ];
        $breakdown = (array) ($match->score_breakdown ?? []);
        $factorScores = [];
        foreach ($groups as $group) {
            $scored = 0.0;
            $possible = 0.0;
            $standalone = [];
            foreach ($breakdown as $key => $item) {
                $normalizedKey = strtolower((string) $key);
                if (!collect($group['keys'])->contains(fn ($keyword) => str_contains($normalizedKey, $keyword))) {
                    continue;
                }
                if (is_array($item) && is_numeric($item['weight'] ?? null) && (float) $item['weight'] > 0 && is_numeric($item['score'] ?? null)) {
                    $possible += (float) $item['weight'];
                    $scored += max(0, min((float) $item['weight'], (float) $item['score']));
                } elseif (is_array($item) && is_numeric($item['percentage'] ?? null)) {
                    $standalone[] = max(0, min(100, (float) $item['percentage']));
                } elseif (is_numeric($item)) {
                    $standalone[] = max(0, min(100, (float) $item));
                }
            }
            $group['score'] = $possible > 0
                ? (int) round($scored / $possible * 100)
                : (count($standalone) ? (int) round(array_sum($standalone) / count($standalone)) : null);
            unset($group['keys']);
            $factorScores[] = $group;
        }

        $readValues = static function ($raw): array {
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : explode(',', $raw);
            }
            return collect(is_array($raw) ? $raw : [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '' && !is_numeric($item))
                ->map(fn ($item) => trim($item))
                ->unique(fn ($item) => mb_strtolower($item))
                ->values()->all();
        };

        $interests = $readValues($viewer->member?->interests_multi_select);
        $theirInterests = $readValues($person->member?->interests_multi_select);
        $sharedInterests = collect($interests)
            ->filter(fn ($interest) => collect($theirInterests)->contains(fn ($other) => strcasecmp($interest, $other) === 0))
            ->values()->take(10)->all();

        $ourValues = $readValues($viewer->member?->life_values);
        $theirValues = $readValues($person->member?->life_values);
        $sharedValues = collect($ourValues)
            ->filter(fn ($value) => collect($theirValues)->contains(fn ($other) => strcasecmp($value, $other) === 0))
            ->values()->take(8)->all();

        $reasons = $readValues($match->compatibility_reasons);
        $concerns = $readValues($match->compatibility_concerns);
        $recommended = $readValues($match->recommended_actions);

        return view('frontend.member.match_explanation', compact(
            'match', 'viewer', 'person', 'matchActions', 'isShortlisted',
            'factorScores', 'sharedInterests', 'sharedValues',
            'reasons', 'concerns', 'recommended'
        ));
    }
}
