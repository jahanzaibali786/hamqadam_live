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
                'profile_match_for_viewer' => fn ($query) => $query->where('user_id', $viewer->id),
            ])
            ->where('user_type', 'member')
            ->whereKeyNot($viewer->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', fn ($query) => $query->where('hide_profile', 0))
            ->whereDoesntHave('profile_privacy_setting', fn ($privacy) => $privacy->where('invisible_mode', true))
            ->whereNotIn('id', HiddenProfileUser::where('hidden_from_user_id', $viewer->id)->pluck('user_id'));

        $this->excludeIgnored($query, $viewer);
        $this->applyDefaultGenderScope($query, $viewer);
        $this->applyPartnerPreferenceScope($query, $viewer);
        $this->applyFilters($query, $viewer, $filters);
        $this->applySorting($query, $viewer, $filters);

        $results = $query->paginate((int) ($filters['per_page'] ?? 20));

        SearchHistory::create([
            'user_id' => $viewer->id,
            'filters' => $filters,
            'result_count' => $results->total(),
        ]);

        return $results;
    }

    private function excludeIgnored($query, User $viewer): void
    {
        $ignoredIds = IgnoredUser::where('ignored_by', $viewer->id)->pluck('user_id')
            ->merge(IgnoredUser::where('user_id', $viewer->id)->pluck('ignored_by'))
            ->unique()
            ->values();

        $query->whereNotIn('id', $ignoredIds);
    }

    private function applyFilters($query, User $viewer, array $filters): void
    {
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
                    $spiritual->where('sub_caste_id', $filters['sect_id']);
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
            $query->whereHas('education', fn ($education) => $education->where('degree', 'like', '%' . $filters['education'] . '%'));
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
            $query->whereHas('member', function ($member) use ($filters) {
                $member->where('mothere_tongue', $filters['language_id'])
                    ->orWhere('known_languages', 'like', '%' . $filters['language_id'] . '%');
            });
        }

        if (isset($filters['compatibility_min'])) {
            $query->whereHas('profile_match_for_viewer', function ($match) use ($viewer, $filters) {
                $match->where('user_id', $viewer->id)
                    ->where('match_percentage', '>=', (int) $filters['compatibility_min']);
            });
        }

        if (! empty($filters['exclude_viewed'])) {
            $query->whereNotIn('id', ProfileViewer::where('viewed_by', $viewer->id)->pluck('user_id'));
        }

        if (! empty($filters['mutual_match'])) {
            $query->whereHas('received_proposals', function ($proposal) use ($viewer) {
                $proposal->where('interested_by', $viewer->id)
                    ->where('status', ProposalStatus::Accepted->value);
            })->whereHas('sent_proposals', function ($proposal) use ($viewer) {
                $proposal->where('user_id', $viewer->id)
                    ->where('status', ProposalStatus::Accepted->value);
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
                $member->where(function ($q) use ($preference) {
                    $q->whereNull('birthday');
                    $q->orWhere(function ($inner) use ($preference) {
                        if (filled($preference->preferred_age_max)) {
                            $inner->where('birthday', '>=', now()->subYears((int) $preference->preferred_age_max + 1)->toDateString());
                        }

                        if (filled($preference->preferred_age_min)) {
                            $inner->where('birthday', '<=', now()->subYears((int) $preference->preferred_age_min)->toDateString());
                        }
                    });
                });
            });
        }

        if (filled($preference->marital_status_id)) {
            $query->whereHas('member', fn ($member) => $member
                ->where(fn ($q) => $q->whereNull('marital_status_id')->orWhere('marital_status_id', $preference->marital_status_id)));
        }

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

        foreach ([
            'preferred_country_id' => 'country_id',
            'preferred_state_id' => 'state_id',
            'preferred_city_id' => 'city_id',
        ] as $prefCol => $column) {
            if (! filled($preference->{$prefCol})) {
                continue;
            }

            $value = $preference->{$prefCol};

            $query->where(function ($outer) use ($column, $value) {
                $outer->whereDoesntHave('addresses')
                    ->orWhereHas('addresses', fn ($address) => $address
                        ->where(fn ($q) => $q->whereNull($column)->orWhere($column, $value)));
            });
        }

        if (filled($preference->preferred_language_ids) && is_array($preference->preferred_language_ids)) {
            $languageIds = collect($preference->preferred_language_ids)->filter()->values()->all();

            if ($languageIds !== []) {
                $query->where(function ($outer) use ($languageIds) {
                    $outer->whereNull('mothere_tongue')
                        ->orWhereIn('mothere_tongue', $languageIds)
                        ->orWhere(function ($nested) use ($languageIds) {
                            foreach ($languageIds as $languageId) {
                                $nested->orWhere('known_languages', 'like', '%"' . $languageId . '"%');
                            }
                        });
                });
            }
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
