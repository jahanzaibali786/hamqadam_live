<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Family;

use App\Enums\ApiErrorCode;
use App\Enums\GuardianPermission;
use App\Exceptions\ApiException;
use App\Models\FamilyGuardianLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Server-side Guardian authorization. Every protected Guardian endpoint must
 * resolve the caller's link to the profile being acted on and evaluate the
 * actual permission keys — never trust a hidden frontend button.
 *
 * Lifecycle (spec §25): only an APPROVED, non-paused, non-revoked link grants
 * access. Pausing disables delegated access without deleting the relationship;
 * revocation terminates it permanently.
 */
class GuardianAuthService
{
    /**
     * Resolves the caller's approved guardian link to the given profile and
     * enforces the lifecycle + permission key. Throws 403 on any failure.
     */
    public function authorize(User $guardian, int $profileUserId, GuardianPermission $permission): FamilyGuardianLink
    {
        $link = FamilyGuardianLink::query()
            ->where('guardian_user_id', $guardian->id)
            ->where('profile_user_id', $profileUserId)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->first();

        if (! $link) {
            throw new ApiException('No approved guardian relationship with this member.', 403, ApiErrorCode::Forbidden->value);
        }

        if ($link->paused_at !== null) {
            throw new ApiException('Guardian access is currently paused by the member.', 403, ApiErrorCode::Forbidden->value);
        }

        if (! $this->allows($link, $permission)) {
            throw new ApiException('This action is not permitted for your guardian role.', 403, ApiErrorCode::Forbidden->value);
        }

        // Presence for the member's guardian list — best effort, throttled by
        // the same rule as member activity (write only when stale).
        if ($link->last_active_at === null || $link->last_active_at->lt(now()->subMinutes(5))) {
            FamilyGuardianLink::query()->whereKey($link->id)->update(['last_active_at' => now()]);
        }

        return $link;
    }

    /** True when the link carries the permission key (row wins over legacy JSON). */
    public function allows(FamilyGuardianLink $link, GuardianPermission $permission): bool
    {
        $row = DB::table('guardian_permissions')
            ->where('guardian_link_id', $link->id)
            ->where('permission_key', $permission->value)
            ->first();

        if ($row !== null) {
            return (bool) $row->is_allowed;
        }

        // Legacy links created before the granular table: fall back to the
        // JSON column, then to the spec's defaults.
        $permissions = $link->permissions;

        if (is_array($permissions) && $permissions !== []) {
            return in_array($permission->value, $permissions, true);
        }

        return in_array($permission, GuardianPermission::DEFAULTS, true);
    }

    /**
     * Replaces the permission rows for a link, keeping rows for keys the
     * member has not touched and clearing ones they revoked.
     */
    public function syncPermissions(FamilyGuardianLink $link, array $allowedKeys, ?int $grantedBy = null): void
    {
        $validKeys = array_map(fn (GuardianPermission $case) => $case->value, GuardianPermission::cases());

        DB::transaction(function () use ($link, $allowedKeys, $grantedBy, $validKeys): void {
            foreach ($validKeys as $key) {
                $allowed = in_array($key, $allowedKeys, true);

                DB::table('guardian_permissions')->updateOrInsert(
                    ['guardian_link_id' => $link->id, 'permission_key' => $key],
                    [
                        'is_allowed' => $allowed,
                        'granted_by' => $grantedBy,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // Keep the legacy JSON in step so older code paths agree.
            $link->forceFill(['permissions' => array_values(array_intersect($validKeys, $allowedKeys))])->save();
        });
    }
}
