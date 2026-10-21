<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

final class HamqadamQaPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        foreach (['package_payments','payment_refund_requests'] as $table) {
            if (! Schema::hasTable($table)) throw new \RuntimeException("Required staging table is missing: {$table}");
        }
        // Deliberately do not insert fake Paid invoices: they could be mistaken
        // for provider-verified purchases and affect finance reporting.
        $this->command?->warn('QA refund review: use a real test-mode gateway purchase on staging. No simulated Paid records created.');
    }
}
