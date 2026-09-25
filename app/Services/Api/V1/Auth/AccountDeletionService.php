<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Google Play account deletion, in two phases:
 *
 * 1. request() — called immediately when the member taps "Delete Account".
 *    The account hides everywhere (deactivated + deletion_requested_at),
 *    every device token is revoked, and their personal data (PII) is wiped
 *    from the users row and the profile tables right away.
 *
 * 2. purgeExpired() — a scheduled job deletes the soft-deleted rows once the
 *    30-day grace window (undo window / legal retention) closes.
 *
 * PII is destroyed in phase 1 because Google requires that data deletion
 * not wait on an unspecified retention period.
 */
class AccountDeletionService
{
    /** Days a soft-deleted row survives before the permanent purge. */
    public const GRACE_DAYS = 30;

    public function __construct(
        private readonly AuthTokenService $tokenService,
    ) {
    }

    /**
     * POST /auth/account-delete — hides the account, revokes every session
     * and destroys personal data immediately.
     */
    public function request(User $user): void
    {
        if ((int) $user->deactivated === 1 && $user->deletion_requested_at !== null) {
            throw new ApiException('Account deletion has already been requested.', 409, 'deletion_already_requested');
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'deactivated' => 1,
                'deletion_requested_at' => now(),
            ])->save();

            // Log out everywhere, immediately.
            $this->tokenService->revokeAll($user);

            // Destroy PII now, not "eventually" — this is what the Play
            // policy cares about. The row itself remains as a tombstone
            // (soft delete) until the grace window closes.
            $this->anonymizeUserRow($user);
            $this->purgeProfileTables($user);

            // Soft delete (User uses SoftDeletes): hides the tombstone from
            // every normal query until the purge command removes it.
            $user->delete();
        });
    }

    /**
     * Cancels a pending deletion during the grace window. The account comes
     * back as a blank shell — the previously wiped data is gone forever.
     */
    public function cancel(User $user): void
    {
        $user->forceFill([
            'deactivated' => 0,
            'deletion_requested_at' => null,
        ])->save();
    }

    /**
     * Permanent cleanup for rows whose grace window has elapsed. Called by
     * the `accounts:purge-deleted` command (scheduled daily).
     */
    public function purgeExpired(): int
    {
        $cutoff = now()->subDays(self::GRACE_DAYS);

        $users = User::onlyTrashed()
            ->where('deletion_requested_at', '<=', $cutoff)
            ->get();

        foreach ($users as $user) {
            // Resolve FK references that point at this user from other
            // members' data, so the final delete cannot fail. Columns that
            // are NOT NULL get deleted instead of nulled.
            DB::table('gift_transactions')->where('receiver_id', $user->id)->delete();
            DB::table('gift_transactions')->where('sender_id', $user->id)->delete();
            DB::table('help_chat_messages')->where('sender_user_id', $user->id)->delete();
            DB::table('family_private_notes')->where('author_user_id', $user->id)->delete();
            DB::table('profile_verification_requests')->where('reviewed_by', $user->id)->update(['reviewed_by' => null]);
            DB::table('moderation_cases')->where('assigned_to', $user->id)->update(['assigned_to' => null]);

            $user->forceDelete();
        }

        return $users->count();
    }

    private function anonymizeUserRow(User $user): void
    {
        $anonEmail = 'deleted_' . $user->id . '_owner@hamqadam.invalid';
        $anonPhone = 'DEL-' . str_pad((string) $user->id, 10, '0', STR_PAD_LEFT);

        $user->forceFill([
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'name' => 'Deleted User',
            'email' => $anonEmail,
            'phone' => $anonPhone,
            'password' => Hash::make(bin2hex(random_bytes(24))),
            'photo' => null,
            'fcm_token' => null,
            'access_token' => null,
            'provider_id' => null,
            'verification_code' => null,
            'new_email_verificiation_code' => null,
            'email_verified_at' => null,
            'verification_info' => null,
            'remember_token' => null,
            'code' => null,
            'balance' => 0,
            'last_login_ip' => null,
            'last_login_at' => null,
            'last_active_at' => null,
        ])->save();

        // The email column is unique: a real re-registration with the same
        // address must not collide, and the address itself must be gone.
        DB::table('users')->where('id', $user->id)->update(['email' => $anonEmail]);
    }

    private function purgeProfileTables(User $user): void
    {
        $id = $user->id;

        // Tables whose whole row IS this user's data.
        $ownRows = [
            'addresses', 'astrologies', 'attitudes', 'careers', 'education',
            'families', 'hobbies', 'lifestyles', 'physical_attributes',
            'recidencies', 'spiritual_backgrounds', 'partner_expectations',
            'profile_privacy_settings', 'notification_preferences',
            'user_push_tokens', 'user_device_sessions', 'search_histories',
            'saved_searches', 'user_activity_logs', 'notification_delivery_logs',
        ];

        foreach ($ownRows as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->where('user_id', $id)->delete();
            }
        }

        // Photos on disk (verification selfies etc.) if stored locally.
        $requests = DB::table('profile_verification_requests')->where('user_id', $id)->get();

        foreach ($requests as $request) {
            foreach ((array) json_decode((string) $request->images, true) as $image) {
                $path = public_path((string) $image);

                if ($path !== public_path() && is_file($path)) {
                    @unlink($path);
                }
            }
        }

        DB::table('profile_verification_requests')->where('user_id', $id)->delete();
        DB::table('ai_verification_attempts')->where('user_id', $id)->delete();
        DB::table('auth_otp_codes')->where('user_id', $id)->delete();
        DB::table('login_attempts')->where('user_id', $id)->delete();
        DB::table('suspicious_activity_logs')->where('user_id', $id)->delete();
        DB::table('hidden_profile_users')->where('user_id', $id)->orWhere('hidden_from_user_id', $id)->delete();
        DB::table('match_suggestion_feedback')->where('user_id', $id)->delete();

        // The wallet row: coins are non-refundable and non-transferable once
        // the member deletes the account, so the row goes too.
        DB::table('members')->where('user_id', $id)->delete();
    }
}
