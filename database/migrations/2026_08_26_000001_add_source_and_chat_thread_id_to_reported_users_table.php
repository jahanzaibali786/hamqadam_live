<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reported_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('reported_users', 'source')) {
                $table->string('source', 20)->default('profile')->after('reason');
            }

            if (! Schema::hasColumn('reported_users', 'chat_thread_id')) {
                $table->foreignId('chat_thread_id')->nullable()->after('source')->constrained('chat_threads')->nullOnDelete();
            }

            $table->index(['source', 'user_id', 'reported_by'], 'reported_users_source_user_reporter_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reported_users', function (Blueprint $table): void {
            if (Schema::hasColumn('reported_users', 'chat_thread_id')) {
                $table->dropConstrainedForeignId('chat_thread_id');
            }

            if (Schema::hasColumn('reported_users', 'source')) {
                $table->dropColumn('source');
            }

            $table->dropIndex('reported_users_source_user_reporter_idx');
        });
    }
};
