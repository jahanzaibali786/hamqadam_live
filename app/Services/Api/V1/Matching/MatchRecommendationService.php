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
    public function __construct(private readonly CompatibilityScoringService $scoring)
    {
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

    public function recommendations(User $user, int $perPage = 20)
    {
        $limit = min($perPage, (int) (get_setting('ai_match_daily_recommendation_limit') ?: 20));
        $minimumScore = (int) (get_setting('ai_match_minimum_score') ?: 50);

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
                    $query->where('gender', '!=', $user->member->gender);
                }
            })
            ->whereDoesntHave('profile_privacy_setting', fn ($privacy) => $privacy->where('invisible_mode', true))
            ->whereNotIn('id', MatchSuggestionFeedback::where('user_id', $user->id)
                ->whereIn('feedback', ['down', 'pass'])
                ->pluck('suggested_user_id'));
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
