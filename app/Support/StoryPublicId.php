<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Signed, encrypted public story IDs. Characters are safe in a URL path.
 * Internal admin routes and existing integer primary keys remain unchanged.
 */
final class StoryPublicId
{
    public static function encode(int $id): string
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('A valid story ID is required.');
        }

        return rtrim(strtr(Crypt::encryptString((string) $id), '+/', '-_'), '=');
    }

    public static function decode(string $token): ?int
    {
        if (strlen($token) < 40 || strlen($token) > 1024 || ! preg_match('/^[A-Za-z0-9_-]+$/D', $token)) {
            return null;
        }

        try {
            $encoded = strtr($token, '-_', '+/');
            $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $id = Crypt::decryptString($encoded);

            if (! ctype_digit($id) || (int) $id < 1 || (string) (int) $id !== $id) {
                return null;
            }

            return (int) $id;
        } catch (\Throwable) {
            return null;
        }
    }
}
