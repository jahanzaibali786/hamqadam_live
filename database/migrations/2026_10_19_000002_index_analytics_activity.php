<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('analytics_events') && ! Schema::hasIndex('analytics_events', 'ae_user_occurred_20261019_idx')) {
            Schema::table('analytics_events', function (Blueprint $table): void {
                $table->index(['user_id', 'occurred_at'], 'ae_user_occurred_20261019_idx');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('analytics_events') && Schema::hasIndex('analytics_events', 'ae_user_occurred_20261019_idx')) {
            Schema::table('analytics_events', fn (Blueprint $table) => $table->dropIndex('ae_user_occurred_20261019_idx'));
        }
    }
};
