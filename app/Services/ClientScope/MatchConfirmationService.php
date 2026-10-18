<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\MatchSuccess;
use App\Models\ProfileMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** One matching rule for website and mobile: confirmed, mutual, idempotent. */
class MatchConfirmationService
{
    public function __construct(private readonly RewardIssuer $rewards)
    {
    }

    /** @return array{confirmation:MatchSuccess,created:bool,mutual:bool,rewards_issued:bool} */
    public function confirm(User $actor, int $targetId, ?string $note = null): array
    {
        if ((int) $actor->id === $targetId) {
            throw ValidationException::withMessages(['matched_user_id' => 'You cannot confirm your own account.']);
        }
        $target = User::query()->where('user_type', 'member')
            ->where('approved', 1)->where('blocked', 0)->where('deactivated', 0)
            ->find($targetId);
        if (! $target) {
            throw ValidationException::withMessages(['matched_user_id' => 'This member is not available.']);
        }
        $profileMatch = ProfileMatch::query()->where('user_id', $actor->id)
            ->where('match_id', $targetId)->first();
        if (! $profileMatch) {
            throw ValidationException::withMessages(['matched_user_id' => 'This member is not in your recorded matches.']);
        }

        $created = false;
        $mutual = false;
        $confirmation = DB::transaction(function () use ($actor, $targetId, $profileMatch, $note, &$created, &$mutual): MatchSuccess {
            $ids = [(int) $actor->id, $targetId];
            sort($ids, SORT_NUMERIC);
            User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            $existing = MatchSuccess::query()->where('user_id', $actor->id)
                ->where('matched_user_id', $targetId)->where('status', 'got_match')->first();
            if ($existing) {
                $confirmation = $existing;
            } else {
                $confirmation = MatchSuccess::query()->create([
                    'user_id' => $actor->id,
                    'matched_user_id' => $targetId,
                    'profile_match_id' => $profileMatch->id,
                    'status' => 'got_match',
                    'note' => $note,
                    'confirmed_at' => now(),
                ]);
                $created = true;
            }
            $mutual = MatchSuccess::query()->where('user_id', $targetId)
                ->where('matched_user_id', $actor->id)->where('status', 'got_match')->exists();
            return $confirmation;
        });

        $rewarded = false;
        if ($mutual) {
            $rewarded = $this->rewards->issue((int) $actor->id, 'got_match', 'match_success', (int) $confirmation->id);
            $other = MatchSuccess::query()->where('user_id', $targetId)
                ->where('matched_user_id', $actor->id)->where('status', 'got_match')->first();
            if ($other) {
                $rewarded = $this->rewards->issue($targetId, 'got_match', 'match_success', (int) $other->id) || $rewarded;
            }
        }
        return ['confirmation' => $confirmation->fresh(), 'created' => $created,
            'mutual' => $mutual, 'rewards_issued' => $rewarded];
    }
}
