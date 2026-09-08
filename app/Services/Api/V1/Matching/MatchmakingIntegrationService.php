<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Matching;

use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the local AI Matchmaking sidecar.
 *
 * Backend owns the DB and auth. The sidecar runs in a separate process
 * (uvicorn app:app) on a configurable host/port and is stateless: the
 * backend always pushes the full user set via POST /users, then asks for
 * one user's matches via GET /match/{user_id}.
 *
 * Flow used by MatchRecommendationService and MatchController:
 *   1) POST /users        → push logged-in + eligible candidates
 *   2) GET  /match/{uid}  → ranked mutual matches for that user
 *
 * When the sidecar is down we degrade gracefully: the caller falls back
 * to the existing rule-based engine so the app still works offline.
 */
class MatchmakingIntegrationService
{
    public function __construct(
        private readonly string $baseUrl = 'http://127.0.0.1:8001',
        private readonly int $timeout = 10,
    ) {
    }

    public static function bind(): void
    {
        // Registration is handled in AppServiceProvider::register().
    }

    /**
     * Ask the sidecar to score a logged-in user against a candidate list.
     *
     * @param User   $user       logged-in member
     * @param array  $candidates User[] already pre-filtered by the backend
     *                             (blocked/deactivated/ignored excluded)
     * @param int|null $topN      optional top-N cap forwarded to the model
     * @param int|null $minScore  optional min-score filter forwarded to the model
     *
     * @return array{
     *     success: bool,
     *     total_users_evaluated: int,
     *     total_matches: int,
     *     matches: array<array{
     *         user_id:int, candidate_id:string, match_score:int,
     *         compatibility_level:string, match_status:string,
     *         your_preferences_match:array, their_preferences_match:array,
     *         match_reasons:array, concerns:array, recommendations:array
     *     }>,
     *     warnings: array<string>,
     *     model_version: string,
     * }|$this when the sidecar is unreachable
     */
    public function getMatches(
        User $user,
        array $candidates,
        ?int $topN = null,
        ?int $minScore = null,
    ): ?array {
        $profile = $this->toModelProfile($user);

        $users = array_merge([$profile], array_map(
            fn (User $c): array => $this->toModelProfile($c),
            $candidates,
        ));

        // 1) push the full user set so the sidecar can score both directions
        $push = Http::timeout($this->timeout)->post("{$this->baseUrl}/users", [
            'users' => $users,
        ]);

        if (! $push->successful()) {
            return null;
        }

        // 2) pull the ranked matches
        $query = http_build_query(array_filter([
            'top_n'    => $topN !== null ? (int) $topN : null,
            'min_score'=> $minScore !== null ? (int) $minScore : null,
        ], fn ($v) => $v !== null));

        $res = Http::timeout($this->timeout)->get(
            "{$this->baseUrl}/match/{$profile['user_id']}"
            . ($query !== '' ? "?{$query}" : ''),
        );

        if (! $res->successful()) {
            return null;
        }

        $body = $res->json();

        return [
            'success'                 => (bool) ($body['success'] ?? false),
            'model_version'           => $body['model_version'] ?? 'unknown',
            'total_users_evaluated'   => (int) ($body['total_users_evaluated'] ?? 0),
            'total_matches'           => (int) ($body['total_matches'] ?? 0),
            'matches'                 => $this->normaliseMatches($body['matches'] ?? []),
            'warnings'                => $body['warnings'] ?? [],
            'processing_time_ms'      => $body['processing_time_ms'] ?? null,
        ];
    }

    /**
     * One-shot helper for the compatibility preview endpoint
     * (/api/v1/profiles/{profile}/compatibility). Returns a flat
     * score/explanation pair that the controller can merge with the
     * existing stored-or-live rule-based result.
     */
    public function compatibilityPreview(User $viewer, User $candidate): array
    {
        $profile   = $this->toModelProfile($viewer);
        $candidateProfile = $this->toModelProfile($candidate);
        $prefs     = $this->toModelPreferences($viewer);

        $push = Http::timeout(10)->post("{$this->baseUrl}/users", [
            'users' => [$profile, $candidateProfile],
        ]);

        if (! $push->successful()) {
            return [
                'source' => 'sidecar_unavailable',
                'percentage' => 0,
                'breakdown' => [],
                'reasons' => ['AI matchmaking sidecar unreachable — falling back to rule-based scoring.'],
                'explanation' => 'Compatibility preview unavailable from the AI model.',
                'calculated_at' => now()->toISOString(),
            ];
        }

        $res = Http::timeout(10)->get("{$this->baseUrl}/match/{$profile['user_id']}");

        if (! $res->successful()) {
            return [
                'source' => 'sidecar_error',
                'percentage' => 0,
                'breakdown' => [],
                'reasons' => ['AI matchmaking sidecar returned an error — falling back to rule-based scoring.'],
                'explanation' => 'Compatibility preview unavailable from the AI model.',
                'calculated_at' => now()->toISOString(),
            ];
        }

        $body = $res->json();
        $match = collect($body['matches'] ?? [])
            ->firstWhere('candidate_id', $candidateProfile['user_id']);

        if (! $match) {
            return [
                'source' => 'sidecar_no_result',
                'percentage' => 0,
                'breakdown' => [],
                'reasons' => ['Candidate not returned by the AI model.'],
                'explanation' => 'No AI compatibility data available for this pair.',
                'calculated_at' => now()->toISOString(),
            ];
        }

        // Map model output back onto the backend's existing response shape.
        return [
            'source' => 'sidecar',
            'percentage' => (int) ($match['match_score'] ?? 0),
            'breakdown' => $match['your_preferences_match']['criterion_matches'] ?? [],
            'reasons' => $match['match_reasons'] ?? [],
            'explanation' => implode(' ', array_slice($match['match_reasons'] ?? [], 0, 4)),
            'calculated_at' => now()->toISOString(),
        ];
    }

    /**
     * Only for backward compat with callers that want a degraded hint. New
     * callers (MatchRecommendationService) check the null return instead.
     */
    public function degradedResult(
        User $user,
        array $candidates,
        ?int $topN,
        ?int $minScore,
    ): array {
        return [
            'success'               => false,
            'model_version'         => 'degraded',
            'total_users_evaluated' => count($candidates),
            'total_matches'         => 0,
            'matches'               => [],
            'warnings'              => ['AI matchmaking sidecar unreachable — using rule-based scoring only.'],
        ];
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * Convert a Laravel User + relations into the model's UserProfile schema.
     */
    private function toModelProfile(User $user): array
    {
        $member = $user->member;
        $spiritual = $user->spiritual_backgrounds;
        $career = $user->career?->first();
        $education = $user->education?->first();

        $profile = [
            'user_id'             => (string) $user->id,
            'gender'              => $member && $member->gender
                ? ('2' === (string) $member->gender || 'female' === strtolower((string) $member->gender)
                    ? 'female'
                    : 'male')
                : null,
            'age'                 => $this->ageFromBirthday($member?->birthday),
            'height_cm'           => $this->cmFromFeet($user->physical_attributes?->height),
            'marital_status'      => $this->maritalStatusLabel($member?->marital_status_id),
            'mother_tongue'       => $this->languageLabel($member?->mother_tongue),
            'religion'            => $spiritual ? $spiritual->religion?->name : null,
            'sect'                => $spiritual ? $spiritual->sect?->name : null,
            'religious_practice'  => $this->practiceLabel($spiritual?->personal_value),
            'education'           => $education ? $this->educationLabel($education->degree) : null,
            'education_field'     => $education ? $education->field_of_study?->name : null,
            'profession'          => $career ? $this->professionLabel($career->designation) : null,
            'profession_category' => $career ? ($career->profession_category?->name ?? null) : null,
            'employment_status'   => $this->employmentLabel($member?->employment_status) ?? null,
            'monthly_income'      => $this->monthlyIncomeFromRange($member?->annualSalaryRange),
            'location'            => $this->locationMap($user->addresses?->first()),
            'interests'           => $this->arrayOfStrings($member?->interests),
            'hobbies'             => $this->arrayOfStrings($member?->hobbies),
            'personality_traits'  => $this->personalityTraits($user),
            'about_me'            => $member?->introduction,
            'diet'                => $user->lifestyles?->diet,
            'smoking'             => $this->yesNo($user->lifestyles?->smoke),
            'drinking'            => $this->yesNo($user->lifestyles?->drink),
            'exercise'            => $this->exerciseLabel($user->lifestyles?->exercise),
            'social_lifestyle'    => $this->socialLabel($user->lifestyles?->social_lifestyle),
            'marriage_timeline'   => $this->timelineLabel($user->partner_expectations?->marriage_timeline),
            'children_preference' => $this->childrenLabel($user->partner_expectations?->children_preference),
            'relocation'          => $this->relocationLabel($user->member?->willing_to_relocate),
            'career_expectation'  => $this->careerExpectationLabel($user->partner_expectations?->expects_spouse_to_work),
            'lifestyle'           => $user->lifestyles ? [
                'exercise' => $user->lifestyles->exercise,
                'smoking'  => $user->lifestyles->smoke,
                'drinking' => $user->lifestyles->drink,
                'diet'     => $user->lifestyles->diet,
                'social'   => $user->lifestyles->social_lifestyle,
            ] : null,
        ];

        // Remove keys the model treats as optional but backend has nothing for yet.
        // Remove null/empty values. Keep 0 as a valid value (e.g. monthly_income=0).
        $profile = array_filter($profile, fn ($v) => $v !== null && $v !== '' && $v !== [] && $v !== 0);

        // Attach partner_preferences when present — the sidecar needs them for
        // direction-2 (their_preferences_match) scoring. Without it the result is
        // one-sided (direction 1 only) and confidence is capped.
        $prefs = $this->toModelPreferences($user);
        if ($prefs) {
            $profile['partner_preferences'] = $prefs;
        }

        return $profile;
    }

    private function toModelPreferences(User $user): array
    {
        $pref = $user->partner_expectations;
        if (! $pref) {
            return [];
        }

        $result = [];

        if ($pref->preferred_age_min !== null || $pref->preferred_age_max !== null) {
            $result['age_range'] = [
                'min' => $pref->preferred_age_min ?? 0,
                'max' => $pref->preferred_age_max ?? 120,
            ];
        }

        // Height in cm — backend stores feet on member side; the preference
        // table stores metres for partner expectations in some rows. Normalise
        // to cm for the model.
        if ($pref->height_min !== null || $pref->height_max !== null) {
            $result['height_range'] = [
                'min_cm' => $this->cmFromMetresOrFeet($pref->height_min),
                'max_cm' => $this->cmFromMetresOrFeet($pref->height_max),
            ];
        }

        if ($pref->preferred_language_ids) {
            $langs = array_map('strval', (array) $pref->preferred_language_ids);
            if ($langs) {
                $result['mother_tongue'] = $langs;
            }
        }

        if ($pref->preferred_country_id) {
            $result['country'] = [(string) $pref->preferred_country_id];
        }

        if ($pref->preferred_state_id) {
            $result['location'] = [(string) $pref->preferred_state_id];
        }

        if ($pref->preferred_city_id) {
            if (! isset($result['location'])) {
                $result['location'] = [];
            }
            $result['location'][] = (string) $pref->preferred_city_id;
        }

        if ($pref->marital_status_id) {
            $result['marital_status'] = [null === $pref->marital_status_id ? 'any' : $this->maritalStatusLabel($pref->marital_status_id)];
        }            if ($pref->religion_id) {
                $religionName = $this->religionLabel($pref->religion_id);
                if ($religionName) {
                    $result['religion'] = [$religionName];
                }
            }

        $practice = $this->practiceLabel($pref->prayer);
        if ($practice) {
            $result['religious_practice'] = [$practice];
        }

        $eduLabel = $this->educationLabel($pref->education);
        if ($eduLabel) {
            $result['min_education'] = $eduLabel;
        }
        if ($pref->profession) {
            $result['profession'] = [$pref->profession];
        }
        if ($pref->profession_category) {
            $result['profession_category'] = [$pref->profession_category];
        }
        $empLabel = $this->employmentLabel($pref->employment_status);
        if ($empLabel) {
            $result['employment_status'] = [$empLabel];
        }

        if ($pref->income_min !== null || $pref->income_max !== null) {
            $result['income_range'] = [
                'min'       => $pref->income_min ?? 0,
                'max'       => $pref->income_max ?? 999999999,
                'currency'  => 'PKR',
            ];
        }

        $fs = $this->familyStructureLabel($pref->family_type);
        if ($fs) {
            $result['family_structure'] = [$fs];
        }
        $la = $this->livingArrangementLabel($pref->living_arrangement);
        if ($la) {
            $result['living_arrangement'] = [$la];
        }
        $fi = $this->familyInvolvementLabel($pref->family_involvement);
        if ($fi) {
            $result['family_involvement'] = [$fi];
        }

        if ($pref->no_smoking === 1 || $pref->no_smoking === true || strtolower((string) $pref->no_smoking) === 'yes') {
            $result['smoking'] = true;
        }
        if ($pref->no_drinking === 1 || $pref->no_drinking === true || strtolower((string) $pref->no_drinking) === 'yes') {
            $result['drinking'] = false;
        }

        if ($pref->diet) {
            $result['diet'] = [$pref->diet];
        }
        $exerciseLabel = $this->exerciseLabel($pref->exercise);
        if ($exerciseLabel) {
            $result['exercise'] = [$exerciseLabel];
        }
        $socialLabel = $this->socialLabel($pref->social_lifestyle);
        if ($socialLabel) {
            $result['social_lifestyle'] = [$socialLabel];
        }

        if ($pref->partner_personality) {
            $pTraits = array_map('strval', (array) $pref->partner_personality);
            if ($pTraits) {
                $result['personality_traits'] = $pTraits;
            }
        }

        if ($pref->partner_interests) {
            $int = array_map('strval', (array) $pref->partner_interests);
            if ($int) {
                $result['interests'] = $int;
            }
        }

        $tl = $this->timelineLabel($pref->marriage_timeline);
        if ($tl) {
            $result['marriage_timeline'] = [$tl];
        }

        $cl = $this->childrenLabel($pref->children_preference);
        if ($cl) {
            $result['children_preference'] = [$cl];
        }

        $rl = $this->relocationLabel($pref->willing_to_relocate);
        if ($rl) {
            $result['relocation'] = [$rl];
        }

        $ce = $this->careerExpectationLabel($pref->expects_spouse_to_work);
        if ($ce) {
            $result['career_expectation'] = [$ce];
        }

        if ($pref->deal_breakers) {
            $result['deal_breakers'] = array_map('strval', (array) $pref->deal_breakers);
        }

        // Remove empty arrays too — FastAPI rejects [] for object-typed fields.
        return array_filter($result, fn ($v) => $v !== null && $v !== '' && (is_array($v) ? count($v) > 0 : true));
    }

    private function normaliseMatches(array $matches): array
    {
        return array_map(fn (array $m): array => [
            'user_id'                => $m['logged_in_user_id'] ?? '',
            'candidate_id'           => $m['candidate_id'] ?? '',
            'match_score'            => (int) ($m['match_score'] ?? 0),
            'match_percentage'        => (int) ($m['match_score'] ?? 0),
            'compatibility_level'    => $m['compatibility_level'] ?? 'none',
            'match_status'           => $m['match_status'] ?? 'unknown',
            'is_match'               => (bool) ($m['is_match'] ?? false),
            'your_preferences_match' => $m['your_preferences_match'] ?? [],
            'their_preferences_match'=> $m['their_preferences_match'] ?? [],
            'mutual_matched_preferences' => $m['mutual_matched_preferences'] ?? [],
            'one_sided_preferences'  => $m['one_sided_preferences'] ?? [],
            'score_balance'          => $m['score_balance'] ?? null,
            'match_reasons'          => $m['match_reasons'] ?? [],
            'concerns'               => $m['concerns'] ?? [],
            'recommendations'        => $m['recommendations'] ?? [],
            'confidence_score'       => (int) ($m['confidence_score'] ?? 0),
            'model_confidence'       => $this->confidenceLabel((int) ($m['confidence_score'] ?? 0)),
            'processing_time_ms'     => $m['processing_time_ms'] ?? null,
            'ai_enhanced'            => (bool) ($m['ai_enhanced'] ?? false),
        ], $matches);
    }

    private function confidenceLabel(int $score): string
    {
        if ($score >= 75) {
            return 'high';
        }
        if ($score >= 50) {
            return 'medium';
        }

        return 'low';
    }

    // ------------------------------------------------------------------
    // Field mappers — backend DB shape -> model schema
    // ------------------------------------------------------------------

    private function ageFromBirthday(?string $birthday): ?int
    {
        if (! $birthday) {
            return null;
        }

        try {
            $age = (int) now()->parse($birthday)->age;
            // Clamp to safe range for the model.
            return max(18, min(120, $age));
        } catch (\Throwable) {
            return null;
        }
    }

    private function cmFromFeet($feet): ?int
    {
        if ($feet === null || $feet === '' || $feet === '0' || $feet === 0) {
            return null;
        }

        // Normalise to string for parsing.
        $str = (string) $feet;
        $val = (float) $str;

        // If value is already in a plausible cm range (100-250), trust it.
        if ($val >= 150 && $val <= 200) {
            return (int) round($val);
        }
        if ($val >= 200 && $val <= 250) {
            return (int) round($val);
        }

        // If value is in a plausible metres range (1.0-2.5), convert to cm.
        if ($val >= 1 && $val <= 2.5) {
            return (int) round($val * 100);
        }

        // Otherwise assume feet/inch notation: 5.4 -> 5 ft 4 in.
        // Only treat as feet notation if value is in a plausible feet range.
        if ($val >= 3 && $val <= 7.5) {
            $parts = explode('.', $str);
            $ft = (int) $parts[0];
            $inchPart = isset($parts[1]) ? (int) $parts[1] : 0;

            // The inch part may be stored as a 2-digit number (e.g. "04" -> 4 inches)
            // or as a decimal fraction. Handle both.
            $inch = $inchPart;
            if ($inchPart >= 10) {
                // Could be "5.10" meaning 5 ft 10 in, or "5.4" meaning 5 ft 4 in.
                // If the decimal part is >= 10, treat it as full inches.
                $inch = $inchPart;
            } else {
                // Treat as decimal: 5.4 -> 5 ft + 0.4*12 = 4.8 in -> round to 5 in.
                $inch = (int) round($inchPart * 12 / 10);
            }

            return (int) round($ft * 30.48 + $inch * 2.54);
        }

        return null;
    }

    private function cmFromMetresOrFeet($value): int
    {
        if ($value === null) {
            return 0;
        }

        $v = (float) $value;

        // If value looks like metres (1.50 - 2.20), convert to cm.
        if ($v >= 1 && $v <= 2.5) {
            return (int) round($v * 100);
        }

        // Otherwise assume feet (5.0 - 6.5) and convert 5.4 -> 162.56 cm.
        $parts = explode('.', (string) $v);
        $ft = (int) $parts[0];
        $inch = isset($parts[1]) ? (int) ($parts[1] / 100 * 12) : 0;

        if ($inch === 0) {
            $inch = (int) ($parts[1] ?? 0);
        }

        return (int) round($ft * 30.48 + $inch * 2.54);
    }

    private function maritalStatusLabel($id): ?string
    {
        return match ((string) $id) {
            '1', '1', 1 => 'never_married',
            '2', 2     => 'divorced',
            '3', 3     => 'widowed',
            '4', 4     => 'separated',
            default    => null,
        };
    }

    private function languageLabel($id): ?string
    {
        return $id ? (string) $id : null;
    }

    private function religionLabel($id): ?string
    {
        if (! $id) {
            return null;
        }

        return match ((string) $id) {
            '1' => 'Islam',
            '2' => 'Christianity',
            '3' => 'Hinduism',
            '4' => 'Sikhism',
            '5' => 'Judaism',
            '6' => 'Buddhism',
            default => (string) $id,
        };
    }

    private function educationLabel($degree): ?string
    {
        if (! $degree) {
            return null;
        }

        $d = strtolower(trim((string) $degree));

        return match ($d) {
            'high school', 'high_school', 'highschool', 'secondary', 'matric', 'ssc', 'o-level', 'olevel' => 'high_school',
            'diploma', 'diploma_in', 'diploma in' => 'diploma',
            'graduate', 'graduation', 'bachelors', 'bachelors_degree', 'ba', 'bsc', 'bcom', 'btech', 'b.e.', 'b.e', 'llb', 'b.ed', 'bed', 'b.ba', 'b.com', 'b.sc', 'b.tech' => 'bachelors',
            'masters', 'masters_degree', 'ma', 'msc', 'mcom', 'mtech', 'm.e.', 'm.e', 'llm', 'm.ed', 'med', 'mba', 'm.ba', 'm.com', 'm.sc', 'm.tech', 'pg' => 'masters',
            'doctorate', 'phd', 'ph.d.', 'ph.d', 'doctor', 'dr.' => 'doctorate',
            'professional', 'professional_degree', 'chartered', 'ca', 'cpa', 'icwa', 'engineer','engineering' => 'professional',
            default => 'unknown',
        };
    }

    private function professionLabel($designation): ?string
    {
        if (! $designation) {
            return null;
        }

        $d = strtolower(trim((string) $designation));

        // Pass through free-text profession as-is — FastAPI accepts any string array.
        return $designation;
    }

    private function practiceLabel($value): ?string
    {
        return match ((string) $value) {
            'very_practicing', 'practicing', '1'   => 'very_practicing',
            'moderately_practicing', 'moderate', '2' => 'moderately_practicing',
            'not_practicing', 'casual', '3'        => 'not_practicing',
            'cultural_only', 'secular', '4'        => 'not_practicing',
            default                                => null,
        };
    }

    private function employmentLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'government', 'govt', '1'  => 'employed',
            'private', '2'             => 'employed',
            'civil', '3'               => 'employed',
            'defence', '4'             => 'employed',
            'self_employed', 'business_owner', '5', '6' => 'self_employed',
            'unemployed', '7'          => 'unemployed',
            'retired', '8'             => 'retired',
            default                    => 'unknown',
        };
    }

    private function monthlyIncomeFromRange($range): ?int
    {
        if (! $range) {
            return null;
        }

        // annualSalaryRange is an object with min_salary / max_salary in the
        // backend DB (PKR per annum). Take the midpoint as a rough monthly figure.
        $min = (float) ($range->min_salary ?? 0);
        $max = (float) ($range->max_salary ?? $min);

        if ($min <= 0 && $max <= 0) {
            return null;
        }

        $annual = ($min + $max) / 2;

        return (int) round($annual / 12);
    }

    private function locationMap($address): ?array
    {
        if (! $address) {
            return null;
        }

        $loc = [];

        if ($address->city_id) {
            $loc['city'] = (string) $address->city_id;
        }

        if ($address->state_id) {
            $loc['state'] = (string) $address->state_id;
        }

        if ($address->country_id) {
            $loc['country'] = (string) $address->country_id;
        }

        return empty($loc) ? null : $loc;
    }

    /** @return list<string> */
    private function arrayOfStrings($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_map('strval', $value);
        }

        // Some columns store comma-separated strings.
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    /** @return list<string> */
    private function personalityTraits(User $user): array
    {
        $traits = [];

        if ($user->member?->personality) {
            $traits = array_merge($traits, $this->arrayOfStrings($user->member->personality));
        }

        // Pull a couple of behavioural signals out of free text for the model.
        if ($user->member?->introduction) {
            $text = strtolower($user->member->introduction);

            foreach (['family-oriented' => ['family', 'parents', 'home'],
                      'ambitious'     => ['career', 'growth', 'goals', 'business'],
                      'calm'         => ['calm', 'peace', 'patient', 'simple'],
                      'faith-focused'=> ['faith', 'prayer', 'muslim', 'islam'],
                      'social'       => ['friends', 'travel', 'community'],
                      ] as $label => $terms) {
                foreach ($terms as $term) {
                    if (str_contains($text, $term)) {
                        $traits[] = $label;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($traits));
    }

    private function yesNo($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = strtolower((string) $value);

        return match ($v) {
            'yes', '1', 'true', 'smoke'   => true,
            'no', '0', 'false', 'non_smoker' => false,
            default                         => null,
        };
    }

    private function exerciseLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'daily', '6',  '7'  => 'daily',
            'regular', 'regularly', '5' => 'regular',
            'occasional', 'sometimes', '4' => 'occasional',
            'rarely', 'never', '1', '2', '3' => 'never',
            default => null,
        };
    }

    private function socialLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'very_social', '7', '8', '9', '10'     => 'very_social',
            'social', 'balanced', '5', '6'         => 'balanced',
            'reserved', 'homebody', '1', '2', '3', '4' => 'reserved',
            default                               => null,
        };
    }

    private function timelineLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'immediate', 'immediately', 'within_1_month'   => 'immediately',
            'within_3_months', '3_months'                  => 'within_3_months',
            'within_6_months', '6_months'                  => 'within_6_months',
            'within_1_year', '1_year', '12_months'         => 'within_1_year',
            'no_rush', 'no_pressure', 'long_term', 'over_a_year' => 'no_rush',
            default                                        => null,
        };
    }

    private function childrenLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'want_children', 'yes', '1', 'yes_i_want'     => 'want_children',
            'dont_want_children', 'no', '0', 'no_not_interested' => 'dont_want_children',
            'undecided', 'maybe', 'depends', 'neutral'    => 'undecided',
            'open_to_children', 'flexible', 'discuss_later' => 'open_to_children',
            default                                       => null,
        };
    }

    private function relocationLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'willing', 'very_willing', '1', 'yes'              => 'willing',
            'negotiable', 'negotiable_within_country', '2'      => 'negotiable',
            'within_country_only', 'not_abroad', '3'           => 'within_country_only',
            'not_willing', 'no', '0', 'never', '4'             => 'not_willing',
            default => null,
        };
    }

    private function careerExpectationLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'continue_career', 'yes', '1', 'career_first'    => 'continue_career',
            'part_time', 'parttime', '2', 'work_part_time'   => 'part_time',
            'flexible', 'depends_on_mutual_understanding', '3' => 'flexible',
            'stop_after_marriage', 'no', '0', 'house_wife'   => 'stop_after_marriage',
            default => null,
        };
    }

    private function familyStructureLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'nuclear', '1'          => 'nuclear',
            'joint', 'extended', '2', '3' => 'extended',
            'single_parent', '4'    => 'single_parent',
            default                  => null,
        };
    }

    private function livingArrangementLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'with_parents', 'with_family', '1'       => 'with_parents',
            'separate_house', 'own_house', '2'       => 'separate_house',
            'independent', 'alone', '3'              => 'independent',
            'abroad', 'overseas', '4'                => 'abroad',
            'flexible', 'any', '5', 'does_not_matter' => 'flexible',
            default => null,
        };
    }

    private function familyInvolvementLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        return match ((string) $value) {
            'minimal', '1', 'low'   => 'minimal',
            'low', '2'              => 'low',
            'moderate', '3', 'medium' => 'moderate',
            'high', '4', 'very_high', 'involved' => 'high',
            default => null,
        };
    }
}
