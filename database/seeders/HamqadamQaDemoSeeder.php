<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Explicit staging-only QA fixture entrypoint.
 * Never registered in DatabaseSeeder.
 */
final class HamqadamQaDemoSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        $this->call([
            HamqadamQaMembersSeeder::class,
            HamqadamQaConfigurationSeeder::class,
            HamqadamQaActivitySeeder::class,
            HamqadamQaPaymentsSeeder::class,
            HamqadamQaMatchSeeder::class,
            HamqadamQaContentSeeder::class,
        ]);
    }
}
