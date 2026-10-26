<?php

namespace App\Support;

use App\Models\User;

final class ReferralSettingsAccess
{
    /**
     * Referral coin configuration is available only to administrators and
     * sub-administrators. Do not depend on Spatie permissions or addon flags.
     *
     * StaffController stores new sub-admins as user_type=staff with
     * admin_identifier=subadmin. Legacy staff accounts may have only the
     * "Sub Admin" role and are supported for backward compatibility.
     */
    public static function allows(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type !== 'staff') {
            return false;
        }

        if (strtolower(trim((string) $user->admin_identifier)) === 'subadmin') {
            return true;
        }

        return $user->hasRole(['Sub Admin', 'subadmin']);
    }
}
