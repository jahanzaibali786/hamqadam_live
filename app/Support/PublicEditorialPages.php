<?php

namespace App\Support;

final class PublicEditorialPages
{
    public static function defaults(string $key): array
    {
        $pages = config('hamqadam_public_pages', []);
        abort_unless(isset($pages[$key]), 404);
        return $pages[$key];
    }

    public static function copy(string $key): array
    {
        $defaults = self::defaults($key);
        $saved = json_decode((string) get_setting('hq_public_editorial_' . $key, ''), true);

        if (!is_array($saved)) return $defaults;

        // Never accept unrecognized fields from the database in rendered pages.
        return array_replace($defaults, array_intersect_key($saved, $defaults));
    }
}
