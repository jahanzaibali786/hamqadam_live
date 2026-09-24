<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat archive + emoji reactions.
 *
 * Archive is per side (`sender_archived_at` / `receiver_archived_at`) so one
 * member hiding a conversation never hides it for the other. Mute already has
 * its columns (`sender_muted_at` / `receiver_muted_at` from the realtime
 * upgrade) — this migration only adds the archive half.
 *
 * Reactions live in their own table because one message can carry several of
 * them; the unique key keeps a member to a single reaction per message, which
 * is what makes tapping the same emoji again a clean "remove".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_threads', 'sender_archived_at')) {
                $table->timestamp('sender_archived_at')->nullable()->after('receiver_muted_at');
            }
            if (! Schema::hasColumn('chat_threads', 'receiver_archived_at')) {
                $table->timestamp('receiver_archived_at')->nullable()->after('sender_archived_at');
            }
        });

        if (! Schema::hasTable('chat_reactions')) {
            Schema::create('chat_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_id');
                $table->unsignedBigInteger('user_id');
                $table->string('emoji', 16);
                $table->timestamps();

                $table->unique(['chat_id', 'user_id']);
                $table->index(['chat_id', 'emoji']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_reactions');

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'sender_archived_at')) {
                $table->dropColumn('sender_archived_at');
            }
            if (Schema::hasColumn('chat_threads', 'receiver_archived_at')) {
                $table->dropColumn('receiver_archived_at');
            }
        });
    }
};
