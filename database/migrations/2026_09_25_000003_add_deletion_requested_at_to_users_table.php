<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Play account-deletion support: when a member requests account
 * deletion we stamp `deletion_requested_at`. The account hides immediately,
 * their PII is purged, and the row stays only until the 30-day grace window
 * (undo window / legal retention) closes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deletion_requested_at')->nullable()->after('deactivated');
            $table->index(['deletion_requested_at'], 'users_deletion_requested_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_deletion_requested_idx');
            $table->dropColumn('deletion_requested_at');
        });
    }
};
