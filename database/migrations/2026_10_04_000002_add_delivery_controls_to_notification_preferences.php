<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            if (! Schema::hasColumn('notification_preferences', 'quiet_hours_start')) {
                $table->time('quiet_hours_start')->nullable()->after('event_preferences');
                $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
                $table->string('timezone', 64)->default('Asia/Karachi')->after('quiet_hours_end');
                $table->string('digest_frequency', 20)->default('instant')->after('timezone');
                $table->unsignedSmallInteger('reminder_interval_minutes')->default(1440)->after('digest_frequency');
                $table->unsignedTinyInteger('reminder_max_attempts')->default(3)->after('reminder_interval_minutes');
                $table->boolean('engagement_enabled')->default(true)->after('reminder_max_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn(['quiet_hours_start', 'quiet_hours_end', 'timezone', 'digest_frequency', 'reminder_interval_minutes', 'reminder_max_attempts', 'engagement_enabled']);
        });
    }
};
