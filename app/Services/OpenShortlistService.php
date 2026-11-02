<?php

namespace App\Services;

use App\Models\HiddenProfileUser;
use App\Models\IgnoredUser;
use App\Models\Shortlist;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class OpenShortlistService
{
    /**
     * Save a visible member without an interest, package, or coin requirement.
     * Returns null for an unavailable target; otherwise returns whether it was
     * newly created. Chat and proposal permissions remain entirely independent.
     */
    public function add(User $actor, int $targetId): ?bool
    {
        if ($targetId <= 0 || $targetId === (int) $actor->id) return null;

        $target = User::query()->whereKey($targetId)->where('user_type', 'member')
            ->where('approved', 1)->where('blocked', 0)->where('deactivated', 0)
            ->whereHas('member', fn ($q) => $q->where(fn ($m) => $m
                ->where('hide_profile', 0)->orWhereNull('hide_profile')))
            ->whereDoesntHave('profile_privacy_setting', fn ($q) => $q->where('invisible_mode', true))
            ->first();

        if (! $target) return null;
        $ignored = IgnoredUser::query()->where(function ($query) use ($actor, $targetId) {
            $query->where(fn ($side) => $side->where('ignored_by', $actor->id)->where('user_id', $targetId))
                ->orWhere(fn ($side) => $side->where('ignored_by', $targetId)->where('user_id', $actor->id));
        })->exists();
        if ($ignored || HiddenProfileUser::where('user_id', $targetId)
            ->where('hidden_from_user_id', $actor->id)->exists()) return null;

        $result = DB::transaction(function () use ($actor, $targetId) {
            // Serializes concurrent requests from the same user.
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $existing = Shortlist::where('shortlisted_by', $actor->id)->where('user_id', $targetId)->first();
            if ($existing) return false;
            Shortlist::create(['shortlisted_by' => $actor->id, 'user_id' => $targetId]);
            return true;
        }, 3);

        if ($result) {
            try {
                $shortlist = Shortlist::where('shortlisted_by', $actor->id)->where('user_id', $targetId)->first();
                Notification::send($target, new DbStoreNotification(
                    'shortlist', unique_notify_id(), $actor->id, $shortlist->id,
                    trim($actor->first_name.' '.$actor->last_name).' has shortlisted you.',
                    route('my_shortlists')
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Shortlist notification failed', [
                    'shortlisted_by' => $actor->id, 'target_id' => $targetId,
                ]);
            }
        }

        return $result;
    }
}
