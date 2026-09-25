<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Api\V1\Auth\AccountDeletionService;
use Illuminate\Console\Command;

/**
 * Permanently deletes accounts whose 30-day post-deletion grace window has
 * elapsed. Scheduled daily in app/Console/Kernel.php.
 */
class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deleted {--dry-run : Count the accounts that would be purged without deleting anything}';

    protected $description = 'Permanently purge accounts deleted more than 30 days ago (Google Play data-deletion policy)';

    public function handle(AccountDeletionService $deletion): int
    {
        if ($this->option('dry-run')) {
            $count = \App\Models\User::onlyTrashed()
                ->where('deletion_requested_at', '<=', now()->subDays(AccountDeletionService::GRACE_DAYS))
                ->count();

            $this->info("{$count} account(s) are due for permanent purge.");

            return self::SUCCESS;
        }

        $purged = $deletion->purgeExpired();

        $this->info("Purged {$purged} account(s) permanently.");

        return self::SUCCESS;
    }
}
