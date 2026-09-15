<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat presence: the last moment the member actively used the app.
 *
 * Written by App\Http\Middleware\EnsureApiMemberActivity on every
 * authenticated API request (throttled to once a minute per member) and read by
 * the chat resources so the app can show "Online" / "Last seen …" under the
 * conversation header.
 *
 * Guarded because the column was hand-added on some databases before this
 * migration existed; running it there used to abort the whole `migrate` batch
 * and leave every later migration (badges, manual-review window) unapplied.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_active_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('last_active_at')->nullable()->after('last_login_at');
            });
        }

        if (! $this->hasIndex('users', 'users_last_active_at_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('last_active_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('users', 'users_last_active_at_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(['last_active_at']);
            });
        }

        if (Schema::hasColumn('users', 'last_active_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('last_active_at');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $i) => ($i['name'] ?? null) === $index);
    }
};
