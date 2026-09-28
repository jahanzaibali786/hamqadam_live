<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Help Center ticket lock — when the admin replies and the member does not
| respond within the response window, the conversation locks itself:
|
|   admin_replied_at   stamped every time the panel sends a message
|   member_replied_at  stamped every time the member sends one
|   locked_at          stamped when the lock check finds the window expired
|
| A locked thread is closed for the member (the app shows "Start New chat"),
| and the member's next message opens a FRESH thread — so the one-thread-per-
| member UNIQUE index on user_id becomes a plain index: a member can now have
| several tickets over time, exactly like the admin panel's list implies.
*/

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('help_chat_threads')) {
            return;
        }

        if (! Schema::hasColumn('help_chat_threads', 'admin_replied_at')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->timestamp('admin_replied_at')->nullable()->after('last_message_at');
                $table->timestamp('member_replied_at')->nullable()->after('admin_replied_at');
                $table->timestamp('locked_at')->nullable()->after('member_replied_at');
            });
        }

        // "Start New chat" needs a second thread row for the same member.
        // The user_id UNIQUE index doubles as the FK's supporting index, so
        // MySQL refuses to drop it while the constraint stands (errno 1553):
        // drop the FK first, then the unique, then re-add the FK — it will
        // lean on the plain index created afterwards.
        if ($this->indexExists('help_chat_threads', 'help_chat_threads_user_id_unique')) {
            if ($this->foreignKeyExists('help_chat_threads', 'help_chat_threads_user_id_foreign')) {
                Schema::table('help_chat_threads', function (Blueprint $table): void {
                    $table->dropForeign('help_chat_threads_user_id_foreign');
                });
            }
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->dropUnique('help_chat_threads_user_id_unique');
            });
        }
        if (! $this->indexExists('help_chat_threads', 'help_chat_threads_user_id_index')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->index('user_id', 'help_chat_threads_user_id_index');
            });
        }
        if (! $this->foreignKeyExists('help_chat_threads', 'help_chat_threads_user_id_foreign')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('help_chat_threads')) {
            return;
        }

        if ($this->indexExists('help_chat_threads', 'help_chat_threads_user_id_index')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->dropIndex('help_chat_threads_user_id_index');
            });
        }
        if (! $this->indexExists('help_chat_threads', 'help_chat_threads_user_id_unique')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->unique('user_id', 'help_chat_threads_user_id_unique');
            });
        }
        if (Schema::hasColumn('help_chat_threads', 'locked_at')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->dropColumn(['locked_at', 'member_replied_at', 'admin_replied_at']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return Schema::getConnection()
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [];
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return Schema::getConnection()
            ->select(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS '
                .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
                [$table, $constraint],
            ) !== [];
    }
};
