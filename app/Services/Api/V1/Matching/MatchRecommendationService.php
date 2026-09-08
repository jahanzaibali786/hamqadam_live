<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Matching;

use App\Models\IgnoredUser;
use App\Models\MatchSuggestionFeedback;
use App\Models\ProfileMatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MatchRecommendationService
{
    public function __construct(
        private readonly CompatibilityScoringService $scoring,
        private readonly MatchmakingIntegrationService $sidecar,
    )
    {
    }

    public function recommendations(User $user, int $perPage = 20)
    {
        $sidecar = $this->sidecar;
        $limit = min($perPage, (int) (get_setting('ai_match_daily_recommendation_limit') ?: 20));
        $minimumScore = (int) (get_setting('ai_match_minimum_score') ?: 50);

        // Ask the sidecar for a ranked list. It returns a compact shape we can
        // map back onto ProfileMatch rows so the rest of the app (resources,
        // feedback loops, pagination) keeps working unchanged.
        $sidecarResult = $sidecar->getMatches(
            $user,
            $this->candidateQuery($user)->limit($limit)->get()->all(),
            $limit,
            $minimumScore,
        );

        if ($sidecarResult !== null) {
            $created = 0;

            foreach ($sidecarResult['matches'] as $m) {
                ProfileMatch::updateOrCreate(
                    ['user_id' => $user->id, 'match_id' => (int) $m['candidate_id']],
                    [
                        'match_percentage'            => (int) ($m['match_score'] ?? 0),
                        'match_status'               => $m['match_status'] ?? 'unknown',
                        'compatibility_level'        => $m['compatibility_level'] ?? 'none',
                        'score_breakdown'            => $m['your_preferences_match'] ?? [],
                        'score_breakdown_their'      => $m['their_preferences_match'] ?? [],
                        'mutual_matched_preferences' => $m['mutual_matched_preferences'] ?? [],
                        'one_sided_preferences'      => $m['one_sided_preferences'] ?? [],
                        'score_balance'              => $m['score_balance'] ?? null,
                        'compatibility_reasons'      => $m['match_reasons'] ?? [],
                        'compatibility_concerns'     => $m['concerns'] ?? [],
                        'recommended_actions'        => $m['recommendations'] ?? [],
                        'confidence_score'           => (int) ($m['confidence_score'] ?? 0),
                        'model_confidence'           => $m['model_confidence'] ?? 'low',
                        'ai_enhanced'                => (bool) ($m['ai_enhanced'] ?? false),
                        'calculated_at'              => $m['processing_time_ms'] ? now() : now(),
                    ]
                );

                $created++;
            }

            return ProfileMatch::query()
                ->with(['matchedUser.member', 'matchedUser.physical_attributes', 'matchedUser.spiritual_backgrounds'])
                ->where('user_id', $user->id)
                ->where('match_percentage', '>=', $minimumScore)
                ->whereNotIn('match_id', MatchSuggestionFeedback::where('user_id', $user->id)
                    ->whereIn('feedback', ['down', 'pass'])
                    ->pluck('suggested_user_id'))
                ->orderByDesc('match_percentage')
                ->orderByDesc('calculated_at')
                ->paginate(max(1, $limit))
                ->tap(fn ($paginator) => $paginator->setCollection(
                    $paginator->getCollection()->map(function (ProfileMatch $pm) use ($sidecarResult) {
                        $pm->setRelation('source_meta', [
                            'source'            => $sidecarResult['success'] ? 'ai_sidecar' : 'rule_based',
                            'model_version'     => $sidecarResult['model_version'],
                            'total_evaluated'   => $sidecarResult['total_users_evaluated'],
                            'warnings'          => $sidecarResult['warnings'],
                        ]);

                        return $pm;
                    })
                ));
        }

        return ProfileMatch::query()
            ->with(['matchedUser.member', 'matchedUser.physical_attributes', 'matchedUser.spiritual_backgrounds'])
            ->where('user_id', $user->id)
            ->where('match_percentage', '>=', $minimumScore)
            ->whereNotIn('match_id', MatchSuggestionFeedback::where('user_id', $user->id)
                ->whereIn('feedback', ['down', 'pass'])
                ->pluck('suggested_user_id'))
            ->orderByDesc('match_percentage')
            ->orderByDesc('calculated_at')
            ->paginate(max(1, $limit));
    }

    public function recalculateFor(User $user, int $limit = 100): int
    {
        $count = 0;

        $this->candidateQuery($user)
            ->limit($limit)
            ->get()
            ->each(function (User $candidate) use ($user, &$count) {
                $score = $this->scoring->score($user, $candidate);
                $score = $this->applyPhaseOneBoosts($user, $candidate, $score);

                ProfileMatch::updateOrCreate(
                    ['user_id' => $user->id, 'match_id' => $candidate->id],
                    [
                        'match_percentage' => $score['percentage'],
                        'score_breakdown' => $score['breakdown'],
                        'compatibility_reasons' => $score['reasons'],
                        'compatibility_explanation' => $score['explanation'],
                        'calculated_at' => now(),
                    ]
                );

                $count++;
            });

        return $count;
    }

    public function storeFeedback(User $user, int $suggestedUserId, string $feedback, ?string $source = null, ?string $note = null): MatchSuggestionFeedback
    {
        return MatchSuggestionFeedback::updateOrCreate(
            [
                'user_id' => $user->id,
                'suggested_user_id' => $suggestedUserId,
            ],
            [
                'feedback' => $feedback,
                'source' => $source ?: 'daily_recommendation',
                'note' => $note,
            ]
        );
    }

    private function candidateQuery(User $user)
    {
        $ignoredIds = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')
            ->merge(IgnoredUser::where('user_id', $user->id)->pluck('ignored_by'))
            ->unique()
            ->values();

        return User::query()
            ->with([
                'member.annualSalaryRange',
                'addresses',
                'education',
                'career',
                'lifestyles',
                'spiritual_backgrounds',
                'partner_expectations',
            ])
            ->where('user_type', 'member')
            ->whereKeyNot($user->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->where('approved', 1)
            ->whereNotIn('id', $ignoredIds)
            ->whereNotIn('id', $this->dealBreakerUserIds($user))
            ->whereHas('member', function ($query) use ($user) {
                $query->where('hide_profile', 0);

                if (filled($user->member?->gender)) {
                    $myGender = (string) $user->member->gender;
                    $query->whereRaw('IF(gender = ?, FALSE, TRUE)', [$myGender]);
                }
            })
            ->whereDoesntHave('profile_privacy_setting', fn ($privacy) => $privacy->where('invisible_mode', true))
            ->whereNotIn('id', MatchSuggestionFeedback::where('user_id', $user->id)
                ->whereIn('feedback', ['down', 'pass'])
                ->pluck('suggested_user_id'))
            ->tap(fn ($query) => $this->applyPartnerPreferenceFilters($query, $user));
    }

    /**
     * Narrow the candidate pool to the partner preferences the member gave at
     * registration (step 17 -> partner_expectations).
     *
     * Before this, preferences only ever affected the compatibility SCORE -
     * the candidate pool itself ignored them completely, so a member who asked
     * for 22-30 year olds of a given religion was still shown everybody.
     *
     * POLICY, and it matters on this dataset: a candidate is excluded only when
     * their value is KNOWN and falls outside the preference. Candidates whose
     * value is missing are kept, because a null is not a mismatch and profiles
     * here are sparse - filtering on unknowns would collapse the pool to almost
     * nothing. Scoring still ranks the better matches above the unknowns.
     *
     * Deliberately NOT filtered: `education`, `profession` and income. Those are
     * free-text on the preference side but ids/ranges on the member side, so a
     * SQL comparison would silently drop valid candidates. They continue to
     * influence the score instead.
     */
    private function applyPartnerPreferenceFilters($query, User $user): void
    {
        $preference = $user->partner_expectations;

        if (! $preference) {
            return;
        }

        // --- age, derived from members.birthday ---
        $ageMin = $preference->preferred_age_min;
        $ageMax = $preference->preferred_age_max;

        if (filled($ageMin) || filled($ageMax)) {
            $query->whereHas('member', function ($member) use ($ageMin, $ageMax) {
                $member->where(function ($q) use ($ageMin, $ageMax) {
                    $q->whereNull('birthday');

                    $q->orWhere(function ($inner) use ($ageMin, $ageMax) {
                        // Older people have EARLIER birthdays, so the maximum
                        // age becomes the earliest acceptable date. Getting this
                        // backwards silently inverts the whole filter.
                        if (filled($ageMax)) {
                            $inner->where('birthday', '>=', now()->subYears((int) $ageMax + 1)->toDateString());
                        }
                        if (filled($ageMin)) {
                            $inner->where('birthday', '<=', now()->subYears((int) $ageMin)->toDateString());
                        }
                    });
                });
            });
        }

        // --- marital status ---
        if (filled($preference->marital_status_id)) {
            $query->whereHas('member', fn ($member) => $member
                ->where(fn ($q) => $q->whereNull('marital_status_id')
                    ->orWhere('marital_status_id', $preference->marital_status_id)));
        }

        // --- religion / caste -> spiritual_backgrounds ---
        foreach (['religion_id' => 'religion_id', 'caste_id' => 'caste_id'] as $prefCol => $column) {
            if (! filled($preference->{$prefCol})) {
                continue;
            }

            $value = $preference->{$prefCol};

            $query->where(function ($outer) use ($column, $value) {
                $outer->whereDoesntHave('spiritual_backgrounds')
                    ->orWhereHas('spiritual_backgrounds', fn ($sb) => $sb
                        ->where(fn ($q) => $q->whereNull($column)->orWhere($column, $value)));
            });
        }

        /*
         * Height is deliberately NOT filtered. The two sides of the comparison
         * are in different units: partner_expectations.height_min/max are
         * metres (e.g. 1.50-1.75) while physical_attributes.height is stored in
         * feet for many rows (e.g. 5.4). Filtering on that silently excluded a
         * candidate who matched on every other criterion - caught in testing.
         *
         * Until heights are stored in one unit, height only influences the
         * compatibility score, where a wrong comparison costs ranking rather
         * than removing somebody from the results entirely.
         */

        // --- preferred location -> addresses ---
        $locationMap = [
            'preferred_country_id' => 'country_id',
            'preferred_state_id' => 'state_id',
            'preferred_city_id' => 'city_id',
        ];

        foreach ($locationMap as $prefCol => $column) {
            if (! filled($preference->{$prefCol})) {
                continue;
            }

            $value = $preference->{$prefCol};

            $query->where(function ($outer) use ($column, $value) {
                $outer->whereDoesntHave('addresses')
                    ->orWhereHas('addresses', fn ($a) => $a
                        ->where(fn ($q) => $q->whereNull($column)->orWhere($column, $value)));
            });
        }
    }

    private function applyPhaseOneBoosts(User $user, User $candidate, array $score): array
    {
        $boost = 0;
        $reasons = $score['reasons'] ?? [];
        $breakdown = $score['breakdown'] ?? [];

        $positiveFeedbackCount = MatchSuggestionFeedback::where('suggested_user_id', $candidate->id)
            ->whereIn('feedback', ['up', 'like', 'super_like'])
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($positiveFeedbackCount > 0 && get_setting('ai_match_collaborative_filtering_enabled') !== '0') {
            $boost += min(8, $positiveFeedbackCount * 2);
            $reasons[] = 'Similar members showed positive interest in this profile.';
        }

        if ($candidate->last_login_at && Carbon::parse($candidate->last_login_at)->greaterThan(now()->subDays(7))) {
            $boost += (int) (get_setting('ai_match_recency_boost_points') ?: 3);
            $reasons[] = 'This member was active recently.';
        }

        if ($boost > 0) {
            $breakdown['phase_one_boosts'] = [
                'weight' => $boost,
                'matched' => true,
                'score' => $boost,
            ];
        }

        $percentage = min(100, (int) ($score['percentage'] ?? 0) + $boost);

        return [
            'percentage' => $percentage,
            'breakdown' => $breakdown,
            'reasons' => $reasons,
            'explanation' => implode(' ', array_slice($reasons, 0, 4)),
        ];
    }

    private function dealBreakerUserIds(User $user)
    {
        $dealBreakers = collect($user->partner_expectations?->deal_breakers ?: [])
            ->filter()
            ->map(fn ($value) => Str::lower((string) $value));

        if ($dealBreakers->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with(['member', 'lifestyles', 'spiritual_backgrounds'])
            ->where('user_type', 'member')
            ->get()
            ->filter(function (User $candidate) use ($dealBreakers) {
                $text = Str::lower(implode(' ', array_filter([
                    $candidate->member?->introduction,
                    $candidate->member?->future_goals,
                    $candidate->lifestyles?->diet,
                    $candidate->lifestyles?->drink,
                    $candidate->lifestyles?->smoke,
                    $candidate->spiritual_backgrounds?->personal_value,
                ])));

                return $dealBreakers->contains(fn (string $term) => $term !== '' && str_contains($text, $term));
            })
            ->pluck('id');
    }
}
