<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('proposal_meetings')) return;
        if (!Schema::hasColumn('proposal_meetings','reminded_24h_at')) {
            Schema::table('proposal_meetings', function (Blueprint $table) {
                $table->timestamp('reminded_24h_at')->nullable();
                $table->timestamp('reminded_1h_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('proposal_meetings')) return;
        if (Schema::hasColumn('proposal_meetings','reminded_24h_at')) {
            Schema::table('proposal_meetings', fn(Blueprint $table)=>$table->dropColumn(['reminded_24h_at','reminded_1h_at']));
        }
    }
};
