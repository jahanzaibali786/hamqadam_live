<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Granular Guardian permission keys (spec §7) and the convenience presets
 * (§8). The preset is only a UI shortcut — authorization always evaluates the
 * keys stored on the guardian link / permission rows.
 */
enum GuardianPermission: string
{
    case ViewBasicProfile = 'view_basic_profile';
    case ViewPhoto = 'view_photo';
    case ViewFamilyInfo = 'view_family_info';
    case ViewVerificationStatus = 'view_verification_status';
    case ViewAiCompatibility = 'view_ai_compatibility';
    case ViewRecommendedMatches = 'view_recommended_matches';
    case ShortlistMatch = 'shortlist_match';
    case AddGuardianNote = 'add_guardian_note';
    case RecommendMatch = 'recommend_match';
    case SendInterest = 'send_interest';
    case ViewPrivatePhotos = 'view_private_photos';
    case ViewPrivateChat = 'view_private_chat';
    case ViewContactDetails = 'view_contact_details';
    case ReviewProposal = 'review_proposal';
    case ApproveFamilyIntro = 'approve_family_intro';
    case ManageOtherGuardians = 'manage_other_guardians';
    case ViewPayments = 'view_payments';
    case AccountSecurity = 'account_security';
    case DeleteAccount = 'delete_account';

    /** Keys that default to ON when a guardian is created without explicit permissions. */
    public const DEFAULTS = [
        self::ViewBasicProfile,
        self::ViewPhoto,
        self::ViewVerificationStatus,
        self::ViewAiCompatibility,
        self::ViewRecommendedMatches,
    ];

    /** View Only preset (§8). */
    public const PRESET_VIEW_ONLY = [
        self::ViewBasicProfile,
        self::ViewPhoto,
        self::ViewVerificationStatus,
        self::ViewAiCompatibility,
        self::ViewRecommendedMatches,
    ];

    /** Review preset = View Only + review actions. */
    public const PRESET_REVIEW = [
        self::ViewBasicProfile,
        self::ViewPhoto,
        self::ViewVerificationStatus,
        self::ViewAiCompatibility,
        self::ViewRecommendedMatches,
        self::ShortlistMatch,
        self::AddGuardianNote,
        self::ReviewProposal,
    ];

    /** Participate preset = Review + recommendation + family-stage actions. */
    public const PRESET_PARTICIPATE = [
        self::ViewBasicProfile,
        self::ViewPhoto,
        self::ViewVerificationStatus,
        self::ViewAiCompatibility,
        self::ViewRecommendedMatches,
        self::ShortlistMatch,
        self::AddGuardianNote,
        self::ReviewProposal,
        self::RecommendMatch,
        self::ApproveFamilyIntro,
    ];

    /** Sensitive keys that must never default on and are strongly discouraged. */
    public const NEVER_DEFAULT = [
        self::SendInterest,
        self::ViewPrivatePhotos,
        self::ViewPrivateChat,
        self::ViewContactDetails,
        self::ManageOtherGuardians,
        self::ViewPayments,
        self::AccountSecurity,
        self::DeleteAccount,
    ];

    /** All keys with their human labels — used by the API and the website UI. */
    public static function catalog(): array
    {
        return [
            self::ViewBasicProfile->value => 'View approved basic profile',
            self::ViewPhoto->value => 'View approved profile photos',
            self::ViewFamilyInfo->value => 'View approved family details',
            self::ViewVerificationStatus->value => 'View verification badges/status',
            self::ViewAiCompatibility->value => 'View compatibility summary',
            self::ViewRecommendedMatches->value => 'Review recommended matches',
            self::ShortlistMatch->value => 'Shortlist a profile',
            self::AddGuardianNote->value => 'Add guardian notes',
            self::RecommendMatch->value => 'Recommend a match to the member',
            self::SendInterest->value => 'Send interest on behalf of the member',
            self::ViewPrivatePhotos->value => 'View restricted photos',
            self::ViewPrivateChat->value => 'View personal chat',
            self::ViewContactDetails->value => 'View phone/email contact details',
            self::ReviewProposal->value => 'Review proposals',
            self::ApproveFamilyIntro->value => 'Approve family-stage actions',
            self::ManageOtherGuardians->value => 'Add/remove other guardians',
            self::ViewPayments->value => 'View subscriptions and payments',
            self::AccountSecurity->value => 'Change password/OTP/security',
            self::DeleteAccount->value => 'Delete the member account',
        ];
    }

    public static function preset(string $preset): array
    {
        return match ($preset) {
            'view_only' => array_map(fn (self $case) => $case->value, self::PRESET_VIEW_ONLY),
            'review' => array_map(fn (self $case) => $case->value, self::PRESET_REVIEW),
            'participate' => array_map(fn (self $case) => $case->value, self::PRESET_PARTICIPATE),
            default => array_map(fn (self $case) => $case->value, self::DEFAULTS),
        };
    }
}
