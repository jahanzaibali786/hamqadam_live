<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_delivery_logs')
            || ! Schema::hasColumn('notification_delivery_logs', 'event_key')
            || ! Schema::hasColumn('notification_delivery_logs', 'event_id')) {
            return;
        }

        Schema::table('notification_delivery_logs', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'event_key', 'event_id', 'channel'],
                'notification_delivery_event_dedupe_idx'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_delivery_logs')) {
            Schema::table('notification_delivery_logs', function (Blueprint $table): void {
                $table->dropIndex('notification_delivery_event_dedupe_idx');
            });
        }
    }
};
