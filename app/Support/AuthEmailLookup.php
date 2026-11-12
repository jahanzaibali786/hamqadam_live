<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolve legacy accounts whose email was saved with mixed casing or spaces.
 * Soft-deleted records remain excluded; duplicate normalized identities fail
 * closed rather than authenticating an arbitrary row.
 */
final class AuthEmailLookup
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function find(string $email): ?User
    {
        $normalized = self::normalize($email);

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $matches = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
