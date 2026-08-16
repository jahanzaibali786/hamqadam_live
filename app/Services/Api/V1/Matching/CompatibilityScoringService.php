<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Matching;

use App\Models\IgnoredUser;
use App\Models\ProfileMatch;
use App\Models\Shortlist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CompatibilityScoringService
{
    private const DEFAULT_WEIGHTS = [
        'religion' => 20,
        'lifestyle' => 15,
        'education' => 15,
        'profession' => 10,
        'income' => 10,
        'age' => 10,
        'prayer' => 10,
        'language' => 5,
        'location' => 5,
        'behavior' => 10,
        'personality' => 10,
        'emotional' => 8,
        'communication' => 7,
        'long_term' => 10,
        'mutual_interest' => 5,
        'cold_start' => 5,
    ];

    public function score(User $user, User $candidate): array
    {
        if (! $this->enabled()) {
            return [
                'percentage' => 0,
                'breakdown' => [],
                'reasons' => [],
                'explanation' => 'AI matchmaking is currently disabled by admin.',
            ];
        }

        $user->loadMissing([
            'member',
            'addresses',
            'education',
            'career',
            'lifestyles',
            'spiritual_backgrounds',
            'partner_expectations',
            'shortlist',
        ]);
        $candidate->loadMissing([
            'member',
            'addresses',
            'education',
            'career',
            'lifestyles',
            'spiritual_backgrounds',
            'partner_expectations',
        ]);

        $weights = $this->weights();
        $breakdown = [];
        $reasons = [];
        $totalWeight = max(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            [$matched, $reason] = $this->evaluate($key, $user, $candidate);
            $breakdown[$key] = [
                'weight' => $weight,
                'matched' => $matched,
                'score' => $matched ? $weight : 0,
            ];

            if ($reason !== null) {
                $reasons[] = $reason;
            }
        }

        $score = array_sum(array_column($breakdown, 'score'));
        $percentage = (int) round(($score / $totalWeight) * 100);

        return [
            'percentage' => min(100, max(0, $percentage)),
            'breakdown' => $breakdown,
            'reasons' => $reasons,
            'explanation' => $this->buildExplanation($reasons, $percentage),
        ];
    }

    private function evaluate(string $key, User $user, User $candidate): array
    {
        $preference = $user->partner_expectations;

        return match ($key) {
            'religion' => $this->matchesValue(
                $this->featureEnabled('ai_match_religious_enabled') ? $preference?->religion_id : null,
                $candidate->spiritual_backgrounds?->religion_id,
                'Religion preference matches.'
            ),
            'lifestyle' => $this->matchesText(
                $this->featureEnabled('ai_match_lifestyle_enabled') ? ($preference?->lifestyle ?: $preference?->diet) : null,
                $candidate->lifestyles?->diet,
                'Lifestyle preferences are compatible.'
            ),
            'education' => $this->candidateHasText(
                $candidate->education,
                $preference?->education,
                'Education preference matches.'
            ),
            'profession' => $this->candidateHasText(
                $candidate->career,
                $preference?->profession,
                'Profession preference matches.'
            ),
            'income' => $this->incomeMatches($preference, $candidate),
            'age' => $this->ageMatches($preference, $candidate),
            'prayer' => $this->matchesText(
                $preference?->prayer,
                $candidate->spiritual_backgrounds?->personal_value,
                'Religious practice preference looks compatible.'
            ),
            'language' => $this->languageMatches($preference, $candidate),
            'location' => $this->locationMatches($preference, $candidate),
            'behavior' => $this->behaviorMatches($user, $candidate),
            'personality' => $this->personalityMatches($user, $candidate),
            'emotional' => $this->emotionalCompatibilityMatches($user, $candidate),
            'communication' => $this->communicationStyleMatches($user, $candidate),
            'long_term' => $this->longTermCompatibilityMatches($user, $candidate),
            'mutual_interest' => $this->mutualInterestMatches($user, $candidate),
            'cold_start' => $this->coldStartCoverageMatches($user, $candidate),
            default => [false, null],
        };
    }

    private function matchesValue(mixed $expected, mixed $actual, string $reason): array
    {
        if (blank($expected)) {
            return [true, null];
        }

        return [(string) $expected === (string) $actual, (string) $expected === (string) $actual ? $reason : null];
    }

    private function matchesText(?string $expected, ?string $actual, string $reason): array
    {
        if (blank($expected)) {
            return [true, null];
        }

        if (blank($actual)) {
            return [false, null];
        }

        $matched = Str::contains(Str::lower($actual), Str::lower($expected))
            || Str::contains(Str::lower($expected), Str::lower($actual));

        return [$matched, $matched ? $reason : null];
    }

    private function candidateHasText($collection, ?string $expected, string $reason): array
    {
        if (blank($expected)) {
            return [true, null];
        }

        $matched = $collection->contains(function ($item) use ($expected) {
            return collect(['degree', 'institution', 'designation', 'company'])
                ->contains(fn ($field) => Str::contains(Str::lower((string) ($item->{$field} ?? '')), Str::lower($expected)));
        });

        return [$matched, $matched ? $reason : null];
    }

    private function incomeMatches($preference, User $candidate): array
    {
        if (blank($preference?->income_min) && blank($preference?->income_max)) {
            return [true, null];
        }

        $salaryRange = $candidate->member?->annualSalaryRange;
        if (! $salaryRange) {
            return [false, null];
        }

        $minOk = blank($preference->income_min) || (float) $salaryRange->max_salary >= (float) $preference->income_min;
        $maxOk = blank($preference->income_max) || (float) $salaryRange->min_salary <= (float) $preference->income_max;
        $matched = $minOk && $maxOk;

        return [$matched, $matched ? 'Income range is within preference.' : null];
    }

    private function ageMatches($preference, User $candidate): array
    {
        if (blank($preference?->preferred_age_min) && blank($preference?->preferred_age_max)) {
            return [true, null];
        }

        if (! $candidate->member?->birthday) {
            return [false, null];
        }

        $age = Carbon::parse($candidate->member->birthday)->age;
        $minOk = blank($preference->preferred_age_min) || $age >= (int) $preference->preferred_age_min;
        $maxOk = blank($preference->preferred_age_max) || $age <= (int) $preference->preferred_age_max;
        $matched = $minOk && $maxOk;

        return [$matched, $matched ? 'Age is within preferred range.' : null];
    }

    private function languageMatches($preference, User $candidate): array
    {
        $preferred = collect($preference?->preferred_language_ids ?: [])
            ->filter()
            ->map(fn ($id) => (string) $id);

        if ($preferred->isEmpty() && blank($preference?->language_id)) {
            return [true, null];
        }

        $candidateLanguages = collect(json_decode((string) $candidate->member?->known_languages, true) ?: [])
            ->push($candidate->member?->mothere_tongue)
            ->filter()
            ->map(fn ($id) => (string) $id);

        if (filled($preference?->language_id)) {
            $preferred->push((string) $preference->language_id);
        }

        $matched = $preferred->intersect($candidateLanguages)->isNotEmpty();

        return [$matched, $matched ? 'Language preference matches.' : null];
    }

    private function locationMatches($preference, User $candidate): array
    {
        if (blank($preference?->preferred_city_id) && blank($preference?->preferred_state_id) && blank($preference?->preferred_country_id)) {
            return [true, null];
        }

        $matched = $candidate->addresses->contains(function ($address) use ($preference) {
            return (filled($preference?->preferred_city_id) && (string) $address->city_id === (string) $preference->preferred_city_id)
                || (filled($preference?->preferred_state_id) && (string) $address->state_id === (string) $preference->preferred_state_id)
                || (filled($preference?->preferred_country_id) && (string) $address->country_id === (string) $preference->preferred_country_id);
        });

        return [$matched, $matched ? 'Location preference matches.' : null];
    }

    private function behaviorMatches(User $user, User $candidate): array
    {
        $ignored = IgnoredUser::where(function ($query) use ($user, $candidate) {
            $query->where('ignored_by', $user->id)->where('user_id', $candidate->id);
        })->orWhere(function ($query) use ($user, $candidate) {
            $query->where('ignored_by', $candidate->id)->where('user_id', $user->id);
        })->exists();

        if ($ignored) {
            return [false, null];
        }

        $shortlisted = Shortlist::where('shortlisted_by', $user->id)->where('user_id', $candidate->id)->exists();

        return [true, $shortlisted ? 'You already shortlisted this profile.' : 'No negative behavior signals found.'];
    }

    private function personalityMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_personality_enabled')) {
            return [true, null];
        }

        $overlap = $this->traitOverlap($user, $candidate);

        return [
            $overlap >= (int) $this->setting('ai_match_trait_overlap_minimum', 1),
            $overlap > 0 ? 'Personality signals from profile text look compatible.' : null,
        ];
    }

    private function emotionalCompatibilityMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_emotional_enabled')) {
            return [true, null];
        }

        $userText = $this->profileText($user);
        $candidateText = $this->profileText($candidate);
        $signals = ['respect', 'kind', 'calm', 'family', 'support', 'peace', 'honest', 'patient'];
        $matched = collect($signals)->contains(
            fn (string $signal) => str_contains($userText, $signal) && str_contains($candidateText, $signal)
        );

        return [$matched, $matched ? 'Emotional values show a healthy overlap.' : null];
    }

    private function communicationStyleMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_communication_enabled')) {
            return [true, null];
        }

        $userStyle = $this->communicationStyle($user);
        $candidateStyle = $this->communicationStyle($candidate);
        $matched = $userStyle === $candidateStyle || in_array('balanced', [$userStyle, $candidateStyle], true);

        return [$matched, $matched ? 'Communication style appears compatible.' : null];
    }

    private function longTermCompatibilityMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_long_term_enabled')) {
            return [true, null];
        }

        $matched = $this->matchesText($user->member?->future_goals, $candidate->member?->future_goals, '')[0]
            || $this->traitOverlap($user, $candidate) >= 2;

        return [$matched, $matched ? 'Long-term goals and profile values show promising alignment.' : null];
    }

    private function mutualInterestMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_mutual_interest_enabled')) {
            return [true, null];
        }

        $candidatePreference = $candidate->partner_expectations;
        $ageOk = $this->ageMatches($candidatePreference, $user)[0];
        $religionOk = $this->matchesValue(
            $candidatePreference?->religion_id,
            $user->spiritual_backgrounds?->religion_id,
            ''
        )[0];
        $locationOk = $this->locationMatches($candidatePreference, $user)[0];
        $matched = collect([$ageOk, $religionOk, $locationOk])->filter()->count() >= 2;

        return [$matched, $matched ? 'The candidate preferences also appear to fit your profile.' : null];
    }

    private function coldStartCoverageMatches(User $user, User $candidate): array
    {
        if (! $this->featureEnabled('ai_match_cold_start_enabled')) {
            return [true, null];
        }

        $userSignals = $this->availableSignalCount($user);
        $candidateSignals = $this->availableSignalCount($candidate);
        $matched = min($userSignals, $candidateSignals) >= (int) $this->setting('ai_match_cold_start_minimum_signals', 4);

        return [$matched, $matched ? 'Enough profile data is available for a stable cold-start recommendation.' : null];
    }

    private function buildExplanation(array $reasons, int $percentage): string
    {
        if ($reasons === []) {
            return $percentage >= 50
                ? 'This profile has general compatibility based on your available preferences.'
                : 'Compatibility is limited because several key preferences are missing or unmatched.';
        }

        return implode(' ', array_slice($reasons, 0, 4));
    }

    private function enabled(): bool
    {
        return get_setting('ai_matchmaking_enabled') !== '0';
    }

    private function weights(): array
    {
        return collect(self::DEFAULT_WEIGHTS)
            ->mapWithKeys(fn (int $default, string $key) => [
                $key => max(0, (int) $this->setting('ai_match_weight_' . $key, $default)),
            ])
            ->filter(fn (int $weight) => $weight > 0)
            ->all();
    }

    private function featureEnabled(string $key): bool
    {
        return get_setting($key) !== '0';
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = get_setting($key);

        return filled($value) ? $value : $default;
    }

    private function traitOverlap(User $user, User $candidate): int
    {
        return $this->traits($user)->intersect($this->traits($candidate))->count();
    }

    private function traits(User $user)
    {
        $text = $this->profileText($user);
        $dictionary = [
            'family_oriented' => ['family', 'parents', 'home', 'children'],
            'faith_focused' => ['faith', 'religion', 'prayer', 'muslim', 'islam'],
            'ambitious' => ['career', 'business', 'growth', 'goals', 'study'],
            'calm' => ['calm', 'peace', 'patient', 'simple'],
            'social' => ['friends', 'travel', 'community', 'outgoing'],
            'honest' => ['honest', 'loyal', 'respect', 'trust'],
        ];

        return collect($dictionary)
            ->filter(fn (array $terms) => collect($terms)->contains(fn (string $term) => str_contains($text, $term)))
            ->keys();
    }

    private function communicationStyle(User $user): string
    {
        $text = $this->profileText($user);
        $direct = ['clear', 'direct', 'honest', 'open'];
        $gentle = ['kind', 'soft', 'patient', 'calm'];

        $directScore = collect($direct)->filter(fn (string $term) => str_contains($text, $term))->count();
        $gentleScore = collect($gentle)->filter(fn (string $term) => str_contains($text, $term))->count();

        if ($directScore > $gentleScore) {
            return 'direct';
        }

        if ($gentleScore > $directScore) {
            return 'gentle';
        }

        return 'balanced';
    }

    private function availableSignalCount(User $user): int
    {
        return collect([
            $user->member?->birthday,
            $user->member?->introduction,
            $user->member?->future_goals,
            $user->spiritual_backgrounds?->religion_id,
            $user->spiritual_backgrounds?->personal_value,
            $user->lifestyles?->diet,
            $user->career?->first()?->designation,
            $user->education?->first()?->degree,
            $user->addresses?->first()?->country_id,
        ])->filter(fn ($value) => filled($value))->count();
    }

    private function profileText(User $user): string
    {
        return Str::lower(implode(' ', array_filter([
            $user->member?->introduction,
            $user->member?->future_goals,
            $user->member?->ai_generated_bio,
            $user->spiritual_backgrounds?->personal_value,
            $user->lifestyles?->diet,
            $user->lifestyles?->living_with,
        ])));
    }
}
