<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Search;

use App\Enums\ProposalStatus;
use App\Models\HiddenProfileUser;
use App\Models\IgnoredUser;
use App\Models\ProfileViewer;
use App\Models\SearchHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ProfileSearchService
{
    public function search(User $viewer, array $filters)
    {
        $viewer->loadMissing(['addresses']);

        $query = User::query()
            ->with([
                'member',
                'addresses',
                'physical_attributes',
                'spiritual_backgrounds',
                'lifestyles',
                // The listing card now shows education/career facts too —
                // eager-loaded here so a page of 20 stays ~6 queries.
                'education',
                'career',
                'profile_match_for_viewer' => fn ($query) => $query->where('user_id', $viewer->id),
            ])
            ->where('user_type', 'member')
            ->whereKeyNot($viewer->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', fn ($query) => $query->where('hide_profile', 0))
            ->whereDoesntHave('profile_privacy_setting', fn ($privacy) => $privacy->where('invisible_mode', true))
            ->whereNotIn('users.id', HiddenProfileUser::where('hidden_from_user_id', $viewer->id)->pluck('user_id'));

        $this->excludeIgnored($query, $viewer);
        $this->applyDefaultGenderScope($query, $viewer);

        $applyPartnerPreference = array_key_exists('partner_preference', $filters)
            ? filter_var($filters['partner_preference'], FILTER_VALIDATE_BOOLEAN)
            : request()->boolean('partner_preference');

        if ($applyPartnerPreference) {
            $this->applyPartnerPreferenceScope($query, $viewer);
        }

        $advancedKeys = ['height_min','height_max','income_min','income_max','compatibility_min',
            'exclude_viewed','mutual_match','lifestyle','caste_id','sub_caste_id','sect_id'];
        foreach ($advancedKeys as $key) {
            if (in_array($key, ['exclude_viewed','mutual_match'], true) &&
                ! filter_var($filters[$key] ?? false, FILTER_VALIDATE_BOOLEAN)) continue;
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null && $filters[$key] !== false) {
                abort_unless(app(\App\Services\ClientScope\PremiumEntitlementService::class)->has($viewer,'advanced_filters'),
                    403, 'Advanced filters require an active premium plan.');
                break;
            }
        }
        $this->applyFilters($query, $viewer, $filters);
        if (\Illuminate\Support\Facades\Schema::hasTable('member_profile_boosts')) {
            $query->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM member_profile_boosts boost WHERE boost.user_id = users.id AND boost.ends_at > ?) THEN 1 ELSE 0 END DESC', [now()]);
        }
        $aiScores = $this->ranksByMatch($filters) ? $this->rankPool($viewer, $query) : null;
        if ($aiScores) {
            // Best match first across the WHOLE pool, not just within a page.
            $ids = implode(',', array_map('intval', array_keys($aiScores)));
            $query->orderByRaw("FIELD(users.id, {$ids}) = 0")
                ->orderByRaw("FIELD(users.id, {$ids})");
        } else {
            $this->applySorting($query, $viewer, $filters);
        }

        $results = $query->paginate((int) ($filters['per_page'] ?? 20));

        if ($aiScores) {
            foreach ($results->items() as $user) {
                if (isset($aiScores[$user->id])) {
                    $user->setAttribute('ai_match_percentage', $aiScores[$user->id]);
                }
            }
        }

        // Record the search so members can revisit it later. Default Discover
        // feed loads (no term, no filters — just per_page/sort/pagination)
        // are skipped so the history doesn't flood with identical
        // "all profiles" rows that carry no meaning.
        if ($this->hasRememberableCriteria($filters)) {
            SearchHistory::create([
                'user_id' => $viewer->id,
                'filters' => $filters,
                'result_count' => $results->total(),
            ]);
        }

        return $results;
    }

    /**
     * Whether the filters carry anything worth remembering in history: a
     * free-text term or at least one user-chosen filter. Pagination and
     * sorting plumbing (per_page, page, sort, partner_preference) do not
     * count — without this guard every plain Discover load floods the
     * history with meaningless "all profiles" rows.
     */
    private function hasRememberableCriteria(array $filters): bool
    {
        $rememberableKeys = [
            'search',
            'min_age',
            'max_age',
            'city_id',
            'state_id',
            'country_id',
            'religion_id',
            'caste_id',
            'community_id',
            'marital_status',
            'gender',
            'height_min',
            'height_max',
            'education',
            'profession',
            'verified_only',
            'with_photo',
            'online_now',
        ];

        foreach ($rememberableKeys as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) {
                return true;
            }
        }

        return false;
    }

    private function excludeIgnored($query, User $viewer): void
    {
        $ignoredIds = IgnoredUser::where('ignored_by', $viewer->id)->pluck('user_id')
            ->merge(IgnoredUser::where('user_id', $viewer->id)->pluck('ignored_by'))
            ->unique()
            ->values();

        $query->whereNotIn('users.id', $ignoredIds);
    }

    private function applyFilters($query, User $viewer, array $filters): void
    {
        // Free-text search from the Discover field: name (first / last / full)
        // or member ID. Runs server-side so pagination keeps matching profiles
        // reachable on every page — the client previously had to emulate this
        // locally, which silently hid matches on unloaded pages.
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            if ($term !== '') {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw("CONCAT(TRIM(COALESCE(first_name, '')), ' ', TRIM(COALESCE(last_name, ''))) LIKE ?", [$like])
                        ->orWhere('code', 'like', $like);
                });
            }
        }

        if (! empty($filters['verified_only'])) {
            $query->where('approved', 1);
        }

        if (! empty($filters['photo_only'])) {
            $query->whereNotNull('photo');
        }

        if (! empty($filters['recently_active'])) {
            $query->where('last_login_at', '>=', now()->subDays(30));
        }

        if (! empty($filters['online_now'])) {
            $query->where('last_login_at', '>=', now()->subMinutes((int) (get_setting('search_online_window_minutes') ?: 15)));
        }

        if (! empty($filters['new_profiles']) || ! empty($filters['new_this_week'])) {
            $days = ! empty($filters['new_this_week']) ? 7 : 14;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        if (! empty($filters['age_min']) || ! empty($filters['age_max'])) {
            $query->whereHas('member', function ($member) use ($filters) {
                if (! empty($filters['age_min'])) {
                    $member->where('birthday', '<=', Carbon::now()->subYears((int) $filters['age_min'])->toDateString());
                }

                if (! empty($filters['age_max'])) {
                    $member->where('birthday', '>=', Carbon::now()->subYears(((int) $filters['age_max']) + 1)->addDay()->toDateString());
                }
            });
        }

        if (! empty($filters['height_min']) || ! empty($filters['height_max'])) {
            $query->whereHas('physical_attributes', function ($physical) use ($filters) {
                if (! empty($filters['height_min'])) {
                    $physical->where('height', '>=', $filters['height_min']);
                }

                if (! empty($filters['height_max'])) {
                    $physical->where('height', '<=', $filters['height_max']);
                }
            });
        }

        if (! empty($filters['religion_id']) || ! empty($filters['sect_id']) || ! empty($filters['caste_id']) || ! empty($filters['sub_caste_id'])) {
            $query->whereHas('spiritual_backgrounds', function ($spiritual) use ($filters) {
                if (! empty($filters['religion_id'])) {
                    $spiritual->where('religion_id', $filters['religion_id']);
                }

                if (! empty($filters['sect_id'])) {
                    $spiritual->where('sect_main_id', $filters['sect_id']);
                }

                if (! empty($filters['caste_id'])) {
                    $spiritual->where('caste_id', $filters['caste_id']);
                }

                if (! empty($filters['sub_caste_id'])) {
                    $spiritual->where('sub_caste_id', $filters['sub_caste_id']);
                }
            });
        }

        if (! empty($filters['education'])) {
            $educationTerm = trim((string) $filters['education']);

            $query->whereHas('education', function ($education) use ($educationTerm) {
                $education->where(function ($match) use ($educationTerm) {
                    $match->where('degree_legacy', 'like', '%' . $educationTerm . '%')
                        ->orWhereHas('degree', fn ($degree) => $degree->where('name', 'like', '%' . $educationTerm . '%'))
                        ->orWhereHas('educationLevel', fn ($level) => $level->where('name', 'like', '%' . $educationTerm . '%'));
                });
            });
        }

        if (! empty($filters['profession'])) {
            $query->whereHas('career', fn ($career) => $career->where('designation', 'like', '%' . $filters['profession'] . '%'));
        }

        if (! empty($filters['income_min']) || ! empty($filters['income_max'])) {
            $query->whereHas('member.annualSalaryRange', function ($salary) use ($filters) {
                if (! empty($filters['income_min'])) {
                    $salary->where('max_salary', '>=', $filters['income_min']);
                }

                if (! empty($filters['income_max'])) {
                    $salary->where('min_salary', '<=', $filters['income_max']);
                }
            });
        }

        if (! empty($filters['lifestyle'])) {
            $query->whereHas('lifestyles', function ($lifestyle) use ($filters) {
                $lifestyle->where('diet', 'like', '%' . $filters['lifestyle'] . '%')
                    ->orWhere('living_with', 'like', '%' . $filters['lifestyle'] . '%');
            });
        }

        if (! empty($filters['nearby'])) {
            $viewerAddress = $viewer->addresses->first();
            if ($viewerAddress) {
                $filters['country_id'] = $filters['country_id'] ?? $viewerAddress->country_id;
                $filters['state_id'] = $filters['state_id'] ?? $viewerAddress->state_id;
                $filters['city_id'] = $filters['city_id'] ?? $viewerAddress->city_id;
            }
        }

        if (! empty($filters['international'])) {
            $viewerCountryId = $viewer->addresses->first()?->country_id;
            if ($viewerCountryId) {
                $query->whereHas('addresses', fn ($address) => $address->where('country_id', '!=', $viewerCountryId));
            }
        } elseif (! empty($filters['country_id']) || ! empty($filters['state_id']) || ! empty($filters['city_id'])) {
            $query->whereHas('addresses', function ($address) use ($filters) {
                if (! empty($filters['country_id'])) {
                    $address->where('country_id', $filters['country_id']);
                }

                if (! empty($filters['state_id'])) {
                    $address->where('state_id', $filters['state_id']);
                }

                if (! empty($filters['city_id'])) {
                    $address->where('city_id', $filters['city_id']);
                }
            });
        }
        if (! empty($filters['language_id'])) {
            $motherTongueColumn = Schema::hasColumn('members', 'mothere_tongue')
                ? 'mothere_tongue'
                : (Schema::hasColumn('members', 'mother_tongue') ? 'mother_tongue' : null);

            $query->whereHas('member', function ($member) use ($filters, $motherTongueColumn) {
                if ($motherTongueColumn) {
                    $member->where($motherTongueColumn, $filters['language_id'])
                        ->orWhere('known_languages', 'like', '%' . $filters['language_id'] . '%');
                    return;
                }

                $member->where('known_languages', 'like', '%' . $filters['language_id'] . '%');
            });
        }
        if (isset($filters['compatibility_min'])) {
            $query->whereHas('profile_match_for_viewer', function ($match) use ($viewer, $filters) {
                $match->where('user_id', $viewer->id)
                    ->where('match_percentage', '>=', (int) $filters['compatibility_min']);
            });
        }

        if (! empty($filters['exclude_viewed'])) {
            $query->whereNotIn('users.id', ProfileViewer::where('viewed_by', $viewer->id)->pluck('user_id'));
        }

        if (! empty($filters['mutual_match'])) {
            // An accepted interest from either direction is sufficient; requiring
            // two reciprocal rows makes normal mutually-accepted proposals vanish.
            $query->whereExists(function ($q) use ($viewer) {
                $q->selectRaw('1')->from('express_interests as accepted_interest')
                    ->where('accepted_interest.status', ProposalStatus::Accepted->value)
                    ->where(function ($pair) use ($viewer) {
                        $pair->where(function ($direction) use ($viewer) {
                            $direction->where('accepted_interest.interested_by', $viewer->id)
                                ->whereColumn('accepted_interest.user_id','users.id');
                        })->orWhere(function ($direction) use ($viewer) {
                            $direction->where('accepted_interest.user_id', $viewer->id)
                                ->whereColumn('accepted_interest.interested_by','users.id');
                        });
                    });
            });
        }
    }

    private function applyDefaultGenderScope($query, User $viewer): void
    {
        $viewerGender = (int) ($viewer->member?->gender ?? 0);
        $targetGender = match ($viewerGender) {
            1 => 2,
            2 => 1,
            default => null,
        };

        if ($targetGender === null) {
            return;
        }

        $query->whereHas('member', fn ($member) => $member->where('gender', $targetGender));
    }


    private function applyPartnerPreferenceScope($query, User $viewer): void
    {
        $preference = $viewer->partner_expectations;

        if (! $preference) {
            return;
        }

        if (filled($preference->preferred_age_min) || filled($preference->preferred_age_max)) {
            $query->whereHas('member', function ($member) use ($preference) {
                if (filled($preference->preferred_age_min)) {
                    $member->where(
                        'birthday',
                        '<=',
                        Carbon::now()
                            ->subYears((int) $preference->preferred_age_min)
                            ->toDateString()
                    );
                }

                if (filled($preference->preferred_age_max)) {
                    $member->where(
                        'birthday',
                        '>=',
                        Carbon::now()
                            ->subYears(((int) $preference->preferred_age_max) + 1)
                            ->addDay()
                            ->toDateString()
                    );
                }
            });
        }

        if (filled($preference->marital_status_id)) {
            $query->whereHas(
                'member',
                fn ($member) => $member->where(
                    'marital_status_id',
                    $preference->marital_status_id
                )
            );
        }

        if (filled($preference->religion_id) || filled($preference->caste_id)) {
            $query->whereHas('spiritual_backgrounds', function ($spiritual) use ($preference) {
                if (filled($preference->religion_id)) {
                    $spiritual->where('religion_id', $preference->religion_id);
                }

                if (filled($preference->caste_id)) {
                    $spiritual->where('caste_id', $preference->caste_id);
                }
            });
        }

        if (
            filled($preference->preferred_country_id)
            || filled($preference->preferred_state_id)
            || filled($preference->preferred_city_id)
        ) {
            $query->whereHas('addresses', function ($address) use ($preference) {
                if (filled($preference->preferred_country_id)) {
                    $address->where('country_id', $preference->preferred_country_id);
                }

                if (filled($preference->preferred_state_id)) {
                    $address->where('state_id', $preference->preferred_state_id);
                }

                if (filled($preference->preferred_city_id)) {
                    $address->where('city_id', $preference->preferred_city_id);
                }
            });
        }

        $languageIds = $this->normalizePreferenceIds(
            $preference->preferred_language_ids ?? null
        );

        if ($languageIds !== []) {
            $motherTongueColumn = Schema::hasColumn('members', 'mothere_tongue')
                ? 'mothere_tongue'
                : (Schema::hasColumn('members', 'mother_tongue') ? 'mother_tongue' : null);

            $query->whereHas('member', function ($member) use ($languageIds, $motherTongueColumn) {
                $member->where(function ($languageQuery) use ($languageIds, $motherTongueColumn) {
                    if ($motherTongueColumn) {
                        $languageQuery->whereIn($motherTongueColumn, $languageIds);
                    }

                    $languageQuery->when(
                        $motherTongueColumn,
                        fn ($q) => $q->orWhere(function ($knownLanguages) use ($languageIds) {
                            foreach ($languageIds as $languageId) {
                                $knownLanguages->orWhere(
                                    'known_languages',
                                    'like',
                                    '%"' . $languageId . '"%'
                                );
                            }
                        }),
                        fn ($q) => $q->where(function ($knownLanguages) use ($languageIds) {
                            foreach ($languageIds as $languageId) {
                                $knownLanguages->orWhere(
                                    'known_languages',
                                    'like',
                                    '%"' . $languageId . '"%'
                                );
                            }
                        })
                    );
                });
            });
        }
    }

    private function normalizePreferenceIds(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn ($id) => filled($id))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            return collect(explode(',', $value))
                ->map(fn ($id) => trim($id))
                ->filter(fn ($id) => $id !== '')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [(int) $value];
    }

    /** The default feed and "Best match" are ordered by the AI model. */
    private function ranksByMatch(array $filters): bool
    {
        return in_array((string) ($filters['sort'] ?? ''), ['', 'default', 'compatibility'], true);
    }

    /**
     * Scores the newest slice of the filtered pool with the matchmaking model
     * in one call. Null when the model is unavailable — the feed then falls
     * back to the normal sort instead of failing.
     *
     * @return array<int,int>|null  [user_id => score], best first
     */
    /** Full model answers from the last rankPool() call (for storing). */
    private ?array $lastRanking = null;

    /**
     * Rewrites the viewer's stored profile_matches from the live model, so
     * the website (dashboard, Matched Profiles), which reads stored rows,
     * shows the same people and percentages as the app. Runs at most every
     * 30 minutes per member; when the model is down the old rows stay.
     */
    public function refreshStoredMatches(User $viewer, bool $force = false): bool
    {
        $fresh = \App\Models\ProfileMatch::where('user_id', $viewer->id)
            ->where('calculated_at', '>=', now()->subMinutes(30))
            ->exists();
        if ($fresh && ! $force) {
            return false;
        }

        $this->lastRanking = null;
        $this->search($viewer, ['per_page' => 1]);
        $ranking = $this->lastRanking;
        if (! $ranking) {
            \Illuminate\Support\Facades\Log::info('Stored matches not refreshed: model gave no ranking', ['user_id' => $viewer->id]);

            return false;
        }

        $now = now();
        $rows = [];
        foreach ($ranking as $matchId => $r) {
            $rows[] = [
                'user_id'                   => $viewer->id,
                'match_id'                  => $matchId,
                'match_percentage'          => \App\Models\ProfileMatch::displayPercentage($r['score']),
                'compatibility_reasons'     => json_encode(array_values($r['reasons'])),
                'compatibility_explanation' => implode(' ', array_slice($r['reasons'], 0, 4)) ?: 'Scored by the AI matchmaking model.',
                'score_breakdown'           => json_encode($r['breakdown']),
                'confidence_score'          => max(0, min(100, $r['confidence'])),
                'compatibility_level'       => $r['level'] ?: null,
                'match_status'              => $r['status'] ?: null,
                'calculated_at'             => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
            ];
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($viewer, $rows) {
            \App\Models\ProfileMatch::where('user_id', $viewer->id)->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                \App\Models\ProfileMatch::insert($chunk);
            }
        });
        \Illuminate\Support\Facades\Log::info('Stored matches refreshed from the AI model', ['user_id' => $viewer->id, 'rows' => count($rows)]);

        return true;
    }

    private function rankPool(User $viewer, $query): ?array
    {
        $limit = (int) config('services.matchmaking.rank_pool', 200);
        $ids = (clone $query)->reorder()->latest('users.created_at')->limit($limit)->pluck('users.id')->all();
        if ($ids === []) {
            return null;
        }

        $sidecar = app(\App\Services\Api\V1\Matching\MatchmakingIntegrationService::class);
        $viewer->loadMissing(\App\Services\Api\V1\Matching\MatchmakingIntegrationService::PROFILE_RELATIONS);
        $candidates = User::with(\App\Services\Api\V1\Matching\MatchmakingIntegrationService::PROFILE_RELATIONS)
            ->whereIn('id', $ids)->get()->all();

        try {
            $this->lastRanking = $sidecar->rankCandidatesWithReasons($viewer, $candidates) ?: null;

            return $this->lastRanking === null
                ? null
                : array_map(fn (array $d): int => $d['score'], $this->lastRanking);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function applySorting($query, User $viewer, array $filters): void
    {
        match ($filters['sort'] ?? 'newest') {
            'compatibility' => $query->leftJoin('profile_matches as search_matches', function ($join) use ($viewer) {
                $join->on('search_matches.match_id', '=', 'users.id');
                $join->where('search_matches.user_id', '=', $viewer->id);
            })->orderByDesc('search_matches.match_percentage')->select('users.*'),
            'recently_active' => $query->orderByDesc('last_login_at'),
            default => $query->latest('users.created_at'),
        };
    }
}
