<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * QA-only demo members. The historical implementation used legacy columns
 * that are no longer present and could partially update production accounts.
 */
final class DemoMatrimonialSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        $this->call(HamqadamQaMembersSeeder::class);
    }
}
