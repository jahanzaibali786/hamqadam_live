<?php

declare(strict_types=1);

namespace Database\Seeders;

final class HamqadamQaGuard
{
    public static function assertSafe(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            throw new \RuntimeException('QA demo seeders are restricted to local, testing or staging environments. Never run them in production.');
        }
    }
}
