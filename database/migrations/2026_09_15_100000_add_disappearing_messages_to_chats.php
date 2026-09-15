<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disappearing messages: each chat row can carry its own `expires_at`. The
 * sender picks a TTL (off / 24h / 7d / 90d) when sending; the API filters out
 * expired rows for the receiver's history, and a scheduled prune hard-deletes
 * anything past its deadline.
 *
 * Also stamps the thread with the chosen TTL so the web client can keep the
 * setting on for subsequent messages without re-picking it each time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (! Schema::hasColumn('chats', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('metadata');
                $table->index(['expires_at']);
            }
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_threads', 'disappear_after')) {
                $table->unsignedInteger('disappear_after')->default(0)->after('receiver_muted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'expires_at')) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'disappear_after')) {
                $table->dropColumn('disappear_after');
            }
        });
    }
};
