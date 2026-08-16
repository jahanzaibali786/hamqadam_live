<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Search;

use App\Models\HiddenProfileUser;
use App\Models\SavedSearch;
use App\Models\SearchHistory;
use App\Models\User;

class SavedSearchService
{
    public function list(User $user)
    {
        return SavedSearch::where('user_id', $user->id)->latest()->paginate(20);
    }

    public function store(User $user, array $data): SavedSearch
    {
        return SavedSearch::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'filters' => $data['filters'],
            'notify' => (bool) ($data['notify'] ?? false),
        ]);
    }

    public function delete(User $user, int $id): void
    {
        SavedSearch::where('user_id', $user->id)->whereKey($id)->delete();
    }

    public function history(User $user)
    {
        return SearchHistory::where('user_id', $user->id)->latest()->paginate(20);
    }

    public function hideFrom(User $user, int $hiddenFromUserId): HiddenProfileUser
    {
        return HiddenProfileUser::firstOrCreate([
            'user_id' => $user->id,
            'hidden_from_user_id' => $hiddenFromUserId,
        ]);
    }

    public function unhideFrom(User $user, int $hiddenFromUserId): void
    {
        HiddenProfileUser::where('user_id', $user->id)
            ->where('hidden_from_user_id', $hiddenFromUserId)
            ->delete();
    }
}
