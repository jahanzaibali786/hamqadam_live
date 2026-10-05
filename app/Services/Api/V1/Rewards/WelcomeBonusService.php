<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Rewards;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The 25-coin welcome bonus.
 *
 * Rules the product asked for:
 *  - exactly ONE claim per account, ever;
 *  - the claim button stays disabled until the member is FULLY verified
 *    (`verification_status = 'verified'` or the AI path approved them);
 *  - claiming adds the coins to the member's MAIN wallet
 *    (members.remaining_interest) so Coin History and balance agree.
 */
class WelcomeBonusService
{
    public const COINS = 25;

    /** State payload the app's Redeem section renders from. */
    public function status(User $user): array
    {
        $member = $user->member;

        return [
            'coins' => self::COINS,
            'eligible' => $this->isEligible($member),
            'claimed' => $member?->welcome_bonus_claimed_at !== null,
            'claimed_at' => $member?->welcome_bonus_claimed_at?->toISOString(),
            'claimable' => $member !== null
                && $member->welcome_bonus_claimed_at === null
                && $this->isEligible($member),
            'balance' => (int) ($member?->remaining_interest ?? 0),
        ];
    }

    /** Claims the bonus; throws when not verified or already claimed. */
    public function claim(User $user): array
    {
        $member = $user->member;

        if ($member === null) {
            throw new ApiException('Member profile not found.', 404, ApiErrorCode::NotFound->value);
        }

        if ($member->welcome_bonus_claimed_at !== null) {
            throw new ApiException('You have already claimed your welcome bonus.', 409, ApiErrorCode::Conflict->value);
        }

        if (! $this->isEligible($member)) {
            throw new ApiException(
                'Welcome bonus unlocks once your profile is fully verified.',
                403,
                ApiErrorCode::Forbidden->value
            );
        }

        $balance = DB::transaction(function () use ($member): int {
            $fresh = Member::where('user_id', $member->user_id)->lockForUpdate()->first();

            // Re-check inside the lock: a double-tap must not double-credit.
            if ($fresh === null || $fresh->welcome_bonus_claimed_at !== null) {
                throw new ApiException('You have already claimed your welcome bonus.', 409, ApiErrorCode::Conflict->value);
            }

            $fresh->remaining_interest = (int) $fresh->remaining_interest + self::COINS;
            $fresh->welcome_bonus_claimed_at = now();
            $fresh->save();

            \App\Models\PackageUsage::record(
                (int) $member->user_id,
                'welcome_bonus',
                'Welcome Bonus',
                self::COINS,
                Member::class,
                (int) $member->user_id,
                'Claimed '.self::COINS.' free welcome coins.',
            );

            // Mirror the credit into the reward ledger so `GET /completion/rewards`
            // (the app's Redeem > Reward history list) actually has a row to show.
            // `reward_rule_id` stays null: this is a built-in product reward, not an
            // admin-configured rule. Keyed on the member so a re-entry (blocked by
            // the check above) cannot double-post.
            \App\Models\RewardTransaction::firstOrCreate(
                [
                    'user_id' => (int) $member->user_id,
                    'event_key' => 'welcome_bonus',
                    'reference_type' => Member::class,
                    'reference_id' => (int) $member->user_id,
                ],
                [
                    'coins' => self::COINS,
                    'metadata' => [
                        'title' => 'Welcome Bonus',
                        'description' => 'Claimed '.self::COINS.' free welcome coins.',
                    ],
                ],
            );

            return (int) $fresh->remaining_interest;
        });

        return [
            'claimed' => true,
            'coins' => self::COINS,
            'balance' => $balance,
        ];
    }

    /** FULLY verified = moderator-verified status OR the AI path approved. */
    private function isEligible(?Member $member): bool
    {
        return $member !== null
            && ($member->verification_status === 'verified'
                || $member->ai_verification_status === 'approved');
    }
}
