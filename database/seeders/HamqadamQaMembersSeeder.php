<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reuse explicitly designated QA accounts only. Never reset credentials,
 * manufacture verification, or rewrite genuine member/profile records.
 */
final class HamqadamQaMembersSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();

        if (! Schema::hasTable('users') || ! Schema::hasTable('members')) {
            throw new \RuntimeException('Staging database is missing the users/members tables.');
        }

        $emails = [
            'demo.user@hamqadam.test',
            'ayesha.khan@hamqadam.test',
            'fatima.ahmed@hamqadam.test',
            'sana.malik@hamqadam.test',
        ];
        $count = DB::table('users')->whereIn('email', $emails)->where('user_type', 'member')->count();

        $this->command?->warn("QA demo members found: {$count}. Existing accounts and passwords are untouched.");
        if ($count < 2) {
            $this->command?->warn('Create two test-only member accounts using normal registration on isolated staging, using the QA email addresses above, before running the remaining demo seeders.');
        }
    }
}
