<?php

namespace App\Support;

/**
 * Interpret the real AI /match payload rather than guessing scores for fixed
 * categories. Stored rows may contain the raw criterion_matches array or
 * older keyed breakdowns, so both formats are supported.
 */
final class AiMatchFactorPresenter
{
    private const ICONS = [
        'location' => 'la-map-marker-alt',
        'religion' => 'la-moon',
        'age' => 'la-calendar-alt',
        'marital_status' => 'la-heart',
        'profession' => 'la-briefcase',
        'education' => 'la-graduation-cap',
        'diet' => 'la-utensils',
        'income' => 'la-wallet',
        'height' => 'la-ruler-vertical',
        'mother_tongue' => 'la-language',
        'language' => 'la-language',
        'family' => 'la-users',
        'caste' => 'la-users',
        'sect' => 'la-moon',
    ];

    /**
     * @return array<int, array{label:string,items:array}>
     */
    public static function sections($yourCriteria, $theirCriteria): array
    {
        $sections = [];
        foreach ([
            ['label' => 'Your preferences', 'raw' => $yourCriteria],
            ['label' => 'Their preferences', 'raw' => $theirCriteria],
        ] as $section) {
            $items = self::items($section['raw']);
            if ($items !== []) {
                $sections[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $sections;
    }

    /**
     * @return array<int, array{label:string,icon:string,score:?int,status:string,reason:string,hard_constraint:bool}>
     */
    public static function items($raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (! is_array($raw)) {
            return [];
        }

        // Full sidecar object or a pair of criterion lists.
        if (isset($raw['criterion_matches'])) {
            $raw = $raw['criterion_matches'];
        }

        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $key => $criterion) {
            if (! is_array($criterion)) {
                // Older named-score breakdowns: only display actual percentages.
                if (is_string($key) && is_numeric($criterion)) {
                    $criterion = ['criterion' => $key, 'score' => $criterion];
                } else {
                    continue;
                }
            }

            $name = $criterion['criterion'] ?? (is_string($key) ? $key : null);
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            if (array_key_exists('applicable', $criterion) && $criterion['applicable'] === false) {
                continue;
            }

            $name = strtolower(trim($name));
            $status = strtolower((string) ($criterion['status'] ?? 'unknown'));
            if (! in_array($status, ['match', 'partial_match', 'no_match', 'unknown'], true)) {
                $status = 'unknown';
            }

            $score = is_numeric($criterion['score'] ?? null)
                ? (int) round(max(0, min(100, (float) $criterion['score'])))
                : null;

            // The model may assign an internal neutral 50 to unknown values:
            // do not represent that as a verified 50% compatibility.
            if ($status === 'unknown' && isset($criterion['status'])) {
                $score = null;
            }

            $label = ucwords(str_replace('_', ' ', $name));
            $reason = $criterion['reason'] ?? '';
            $items[] = [
                'label' => $label,
                'icon' => self::ICONS[$name] ?? 'la-check-circle',
                'score' => $score,
                'status' => $status,
                'reason' => is_string($reason) ? trim($reason) : '',
                'hard_constraint' => (bool) ($criterion['is_hard_constraint'] ?? false),
            ];
        }

        return $items;
    }
}
