<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class HamqadamQaContentSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        $this->command?->warn('Use admin content CMS to create unpublished demo blogs. No public content was inserted.');
    }
}
