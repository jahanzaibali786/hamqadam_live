<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat thread delete (Task2): long-press → "Delete conversation".
 *
 * Like archive, delete is per side: the member who deletes hides the thread
 * from THEIR list only — the other person's list is untouched. New incoming
 * messages from the other side un-hide the thread (WhatsApp behaviour): the
 * delete stamp is cleared whenever the other side sends the next message, so
 * a deleted conversation returns exactly like a new chat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_threads', 'sender_deleted_at')) {
                $table->timestamp('sender_deleted_at')->nullable()->after('receiver_archived_at');
            }
            if (! Schema::hasColumn('chat_threads', 'receiver_deleted_at')) {
                $table->timestamp('receiver_deleted_at')->nullable()->after('sender_deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'sender_deleted_at')) {
                $table->dropColumn('sender_deleted_at');
            }
            if (Schema::hasColumn('chat_threads', 'receiver_deleted_at')) {
                $table->dropColumn('receiver_deleted_at');
            }
        });
    }
};
