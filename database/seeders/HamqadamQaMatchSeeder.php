<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class HamqadamQaMatchSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        // A mutual confirmation must be initiated by each logged-in QA member,
        // not manufactured through direct inserts.
        $this->command?->warn('Create match confirmations manually from two QA accounts. No verified or self-reported outcomes were fabricated.');
    }
}
