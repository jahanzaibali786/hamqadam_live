<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'manual_review_started_at')) {
                $table->timestamp('manual_review_started_at')->nullable()->index();
            }
            if (! Schema::hasColumn('members', 'manual_review_expires_at')) {
                $table->timestamp('manual_review_expires_at')->nullable()->index();
            }
        });

        DB::table('members')
            ->where('ai_verification_status', 'manual_review')
            ->whereNull('manual_review_started_at')
            ->update([
                'manual_review_started_at' => DB::raw('COALESCE(ai_verification_last_attempt_at, updated_at)'),
                'manual_review_expires_at' => DB::raw('DATE_ADD(COALESCE(ai_verification_last_attempt_at, updated_at), INTERVAL 12 HOUR)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            foreach (['manual_review_started_at', 'manual_review_expires_at'] as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
