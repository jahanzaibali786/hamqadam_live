<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Matching;

use App\Models\IgnoredUser;
use App\Models\ProfileSwipe;
use App\Models\User;

/**
 * Interest-Based Recommendations.
 *
 * Ranks members by how much of the viewer's own story they share — hobbies,
 * interests, life values, love language, family values and languages — and
 * says WHICH ones, so a card can read "Reads, travels, values honesty" instead
 * of just a percentage.
 *
 * How the stored shapes are read matters here. The sign-up step writes some of
 * these fields as plain comma-separated text (`hobbies`) and others as JSON
 * arrays (`interests_multi_select`, `life_values`, `love_language`,
 * `languages_spoken_fluently`), and older rows also store lookup ids. Both
 * forms are normalised to lower-case words before comparing, so a field never
 * has to be migrated for the comparison to work.
 */
class InterestMatchService
{
    /** Fields compared, with the weight each carries. */
    private const WEIGHTS = [
        'interests_multi_select' => 3.0,
        'hobbies' => 2.5,
        'life_values' => 2.0,
        'family_values' => 1.5,
        'love_language' => 1.0,
        'languages_spoken_fluently' => 0.5,
    ];

    /** Friendly label per field, used in the "why" line. */
    private const LABELS = [
        'interests_multi_select' => 'Shared interests',
        'hobbies' => 'Shared hobbies',
        'life_values' => 'Shared values',
        'family_values' => 'Family values',
        'love_language' => 'Love language',
        'languages_spoken_fluently' => 'Languages',
    ];

    public function __construct(private readonly SwipeDeckService $deck)
    {
    }

    /**
     * Returns [candidates, viewer_terms] where every candidate carries an
     * `interest_score` (0-100) and a `shared_interests` list.
     */
    public function recommend(User $viewer, array $filters = []): array
    {
        $viewerTerms = $this->termsOf($viewer);

        if ($viewerTerms === []) {
            // Every key the controller reads is present, even on this early
            // exit: a member with nothing filled in is a normal state, not an
            // error, and returning a short array here used to blow up the
            // response with an undefined-key 500.
            return [
                'candidates' => collect(),
                'viewer_terms' => [],
                'total' => 0,
                'reason' => 'no_profile_interests',
            ];
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 50));

        // Reuse the deck's visibility rules — one definition of "who may I see".
        $deck = $this->deck->deck($viewer, ['per_page' => 50] + $filters);
        $candidates = $deck['candidates'];

        $scored = $candidates->map(function (User $candidate) use ($viewerTerms) {
            $match = $this->score($viewerTerms, $this->termsOf($candidate));

            $candidate->setAttribute('interest_score', $match['score']);
            $candidate->setAttribute('shared_interests', $match['shared']);
            $candidate->setAttribute('interest_breakdown', $match['breakdown']);

            return $candidate;
        })
            // Only members who actually share something: a 0% card is noise.
            ->filter(fn (User $candidate) => ($candidate->getAttribute('interest_score') ?? 0) > 0)
            ->sortByDesc(fn (User $candidate) => $candidate->getAttribute('interest_score'))
            ->take($perPage)
            ->values();

        return [
            'candidates' => $scored,
            'viewer_terms' => $this->labelsOf($viewerTerms),
            'total' => $scored->count(),
        ];
    }

    /**
     * Weighted overlap. The denominator is the viewer's own total weight, so
     * the percentage answers "how much of what matters to me does this person
     * share?" rather than being diluted by fields the viewer never filled in.
     */
    private function score(array $viewerTerms, array $candidateTerms): array
    {
        $earned = 0.0;
        $possible = 0.0;
        $shared = [];
        $breakdown = [];

        foreach (self::WEIGHTS as $field => $weight) {
            $mine = $viewerTerms[$field] ?? [];
            if ($mine === []) {
                continue;
            }

            $possible += $weight;
            $overlap = array_values(array_intersect($mine, $candidateTerms[$field] ?? []));

            if ($overlap === []) {
                continue;
            }

            $earned += $weight * (count($overlap) / count($mine));
            $shared = array_merge($shared, $overlap);
            $breakdown[] = [
                'field' => $field,
                'label' => self::LABELS[$field],
                'items' => $overlap,
            ];
        }

        return [
            'score' => $possible > 0 ? (int) round(($earned / $possible) * 100) : 0,
            'shared' => array_values(array_unique($shared)),
            'breakdown' => $breakdown,
        ];
    }

    /** Normalised term sets for one member, per compared field. */
    private function termsOf(User $user): array
    {
        $member = $user->member;
        if (! $member) {
            return [];
        }

        $terms = [];
        foreach (array_keys(self::WEIGHTS) as $field) {
            $values = $this->valuesOf($member->{$field} ?? null);
            if ($values !== []) {
                $terms[$field] = $values;
            }
        }

        return $terms;
    }

    /** Ids and words both come back as words the deck can display. */
    private function labelsOf(array $terms): array
    {
        $labels = [];
        foreach ($terms as $field => $values) {
            $labels[] = [
                'field' => $field,
                'label' => self::LABELS[$field],
                'items' => $values,
            ];
        }

        return $labels;
    }

    /**
     * One field → a clean list of comparable words.
     *
     * Handles: JSON array, JSON array stored inside a JSON string (the shape
     * older rows have), plain comma-separated text, and "1,2,3" id lists.
     */
    private function valuesOf(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $values = [];

        if (is_array($raw)) {
            $values = $raw;
        } else {
            $text = trim((string) $raw);

            // A JSON string that itself contains JSON (double-encoded).
            $decoded = json_decode($text, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = preg_split('/\s*,\s*/', $text) ?: [];
            }
        }

        $clean = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                continue;
            }
            $word = mb_strtolower(trim((string) $value));
            $word = trim($word, "\"'[]\\");
            if ($word !== '') {
                $clean[] = $word;
            }
        }

        return array_values(array_unique($clean));
    }

    /** Members this viewer has already passed on are not recommended again. */
    public function excludingPassed(User $viewer): array
    {
        return ProfileSwipe::where('swiper_user_id', $viewer->id)
            ->where('action', ProfileSwipe::PASS)
            ->pluck('target_user_id')
            ->all();
    }

    /** Mirrors the deck's ignore rules for callers that need them standalone. */
    public function ignoredIds(User $viewer): array
    {
        return IgnoredUser::where('ignored_by', $viewer->id)->pluck('user_id')
            ->merge(IgnoredUser::where('user_id', $viewer->id)->pluck('ignored_by'))
            ->unique()
            ->values()
            ->all();
    }
}
