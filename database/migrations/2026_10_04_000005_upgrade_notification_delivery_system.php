<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_delivery_logs')) {
            Schema::table('notification_delivery_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_delivery_logs', 'event_key')) {
                    $table->string('event_key', 120)->nullable()->after('channel');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'event_id')) {
                    $table->string('event_id', 160)->nullable()->after('event_key');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'deep_link')) {
                    $table->string('deep_link', 500)->nullable()->after('payload');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('sent_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('delivered_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'clicked_at')) {
                    $table->timestamp('clicked_at')->nullable()->after('read_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'failure_reason')) {
                    $table->text('failure_reason')->nullable()->after('clicked_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'reminder_count')) {
                    $table->unsignedTinyInteger('reminder_count')->default(0)->after('failure_reason');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'last_reminded_at')) {
                    $table->timestamp('last_reminded_at')->nullable()->after('reminder_count');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_delivery_logs')) {
            return;
        }

        Schema::table('notification_delivery_logs', function (Blueprint $table): void {
            foreach ([
                'event_key', 'event_id', 'deep_link', 'delivered_at', 'read_at',
                'clicked_at', 'failure_reason', 'reminder_count', 'last_reminded_at',
            ] as $column) {
                if (Schema::hasColumn('notification_delivery_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
