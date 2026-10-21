<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('help_chat_threads')) return;
        if (! Schema::hasColumn('help_chat_threads', 'assigned_agent_id')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->unsignedBigInteger('assigned_agent_id')->nullable()->index();
                $table->dateTime('assigned_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('help_chat_threads') && Schema::hasColumn('help_chat_threads', 'assigned_agent_id')) {
            Schema::table('help_chat_threads', function (Blueprint $table): void {
                $table->dropIndex(['assigned_agent_id']);
                $table->dropColumn(['assigned_agent_id', 'assigned_at']);
            });
        }
    }
};
