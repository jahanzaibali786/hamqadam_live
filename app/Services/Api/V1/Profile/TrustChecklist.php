<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Profile;

use App\Enums\VerificationRequestStatus;
use App\Models\AiVerificationAttempt;
use App\Models\Member;
use App\Models\ProfileVerificationRequest;
use App\Models\User;

/**
 * The "Trust & Verification" checklist — one boolean per thing members ask
 * "is this real?" about.
 *
 * Computed HERE on the server from the actual records so every client renders
 * the same picture and — critically — so this section can never disagree with
 * the app's "Identity verification" card: both now read the SAME
 * ProfileVerificationRequest row (documents + face_match verdict).
 *
 *   identity — a moderator approved the CNIC documents (members.verification_status)
 *   face     — a selfie exists and the request carries the moderator's verdict;
 *              while it is still in manual review this is null (unknown), not false
 *   liveness — the server's face-match verdict ("Liveness & face match" on the
 *              Identity card); falls back to the AI pre-screen APPROVE
 *   phone    — the mobile OTP round completed (verification_code consumed)
 *   email    — email_verified_at is stamped
 *   profile  — the account carries the team's approval (admin reviewed)
 *   intent   — the profile was created on the member's behalf (wali/family)
 *
 * null means "no verdict yet" — clients must render a neutral state, never a
 * red cross, for a pending manual review.
 */
class TrustChecklist
{
    /**
     * @param  AiVerificationAttempt|null  $latestAttempt  newest AI pre-screen row
     * @param  ProfileVerificationRequest|null  $request  newest CNIC/selfie request (with documents)
     * @return array<string, bool|null>
     */
    public static function compute(
        ?Member $member,
        User $user,
        ?AiVerificationAttempt $latestAttempt,
        ?ProfileVerificationRequest $request,
    ): array {
        $docs = $request?->documents?->keyBy(fn ($d) => $d->type) ?? collect();
        $hasCnic = $docs->has('cnic_front') && $docs->has('cnic_back');
        $hasSelfie = $docs->has('selfie');

        $status = $request?->status instanceof VerificationRequestStatus
            ? $request->status->value
            : (string) ($request?->status ?? '');

        // A document check with no verdict of its own inherits the moderator's
        // decision on the request as a whole — mirrors the app's Identity card.
        $inherited = static function (bool $submitted) use ($status): ?bool {
            if (! $submitted) {
                return false;
            }
            if ($status === 'approved') {
                return true;
            }
            if ($status === 'rejected') {
                return false;
            }

            return null; // submitted, awaiting the human reviewer
        };

        $faceMatch = match ($request?->face_match_status) {
            'matched' => true,
            'mismatched', 'failed' => false,
            default => null,
        };

        $aiApproved = ($member?->ai_verification_status === 'approved')
            || ($latestAttempt?->recommendation === 'APPROVE');

        return [
            'identity' => ($member?->verification_status === 'verified') || $hasCnic && $status === 'approved',
            'face' => $inherited($hasSelfie),
            'liveness' => $faceMatch ?? ($aiApproved ? true : $inherited($hasSelfie)),
            'phone' => filled($user->phone) && blank($user->verification_code),
            'email' => filled($user->email_verified_at),
            'profile' => (bool) $user->approved,
            'intent' => filled($member?->on_behalves_id) && (int) $member->on_behalves_id > 0,
        ];
    }
}
