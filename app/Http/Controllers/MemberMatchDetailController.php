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
        $match = ProfileMatch::query()
            ->visibleToMember($viewer)
            ->with([
                'matchedUser.member',
                'matchedUser.addresses.city',
                'matchedUser.education',
                'matchedUser.career',
                'matchedUser.spiritual_backgrounds',
                'matchedUser.lifestyles',
            ])
            ->where('match_id', $member)
            ->firstOrFail();

        $person = $match->matchedUser;
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
