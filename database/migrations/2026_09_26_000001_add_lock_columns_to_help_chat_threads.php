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
        if ($this->indexExists('help_chat_threads', 'help_chat_threads_user_id_unique')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->dropUnique('help_chat_threads_user_id_unique');
            });
        }
        if (! $this->indexExists('help_chat_threads', 'help_chat_threads_user_id_index')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->index('user_id', 'help_chat_threads_user_id_index');
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
};
