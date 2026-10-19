<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Deliberately not registered in DatabaseSeeder; only explicit opt-in. */
final class HamqadamQaDemoSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();

        $this->call([
            HamqadamQaConfigurationSeeder::class,
            HamqadamQaActivitySeeder::class,
            HamqadamQaPaymentsSeeder::class,
            HamqadamQaMatchSeeder::class,
            HamqadamQaContentSeeder::class,
        ]);
    }
}
