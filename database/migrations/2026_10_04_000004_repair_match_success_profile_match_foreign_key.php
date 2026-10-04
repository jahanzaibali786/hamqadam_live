<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('match_successes') || ! Schema::hasTable('profile_matches')) {
            return;
        }

        if (! Schema::hasColumn('match_successes', 'profile_match_id')) {
            Schema::table('match_successes', function (Blueprint $table): void {
                $table->bigInteger('profile_match_id')->nullable()->after('matched_user_id');
            });
        }

        $column = DB::selectOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'match_successes'
             AND COLUMN_NAME = 'profile_match_id'"
        );

        if ($column && stripos((string) $column->COLUMN_TYPE, 'unsigned') !== false) {
            DB::statement('ALTER TABLE `match_successes` MODIFY `profile_match_id` BIGINT NULL');
        }

        $constraint = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'match_successes'
             AND COLUMN_NAME = 'profile_match_id'
             AND REFERENCED_TABLE_NAME = 'profile_matches'
             LIMIT 1"
        );

        if ($constraint) {
            return;
        }

        // Existing partial installs may contain orphaned references from the
        // failed migration. A nullable relationship can safely clear those.
        DB::statement(
            'UPDATE `match_successes` AS ms
             LEFT JOIN `profile_matches` AS pm ON pm.`id` = ms.`profile_match_id`
             SET ms.`profile_match_id` = NULL
             WHERE ms.`profile_match_id` IS NOT NULL AND pm.`id` IS NULL'
        );

        Schema::table('match_successes', function (Blueprint $table): void {
            $table->foreign('profile_match_id', 'match_successes_profile_match_id_foreign')
                ->references('id')
                ->on('profile_matches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('match_successes')) {
            return;
        }

        $constraint = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'match_successes'
             AND COLUMN_NAME = 'profile_match_id'
             AND REFERENCED_TABLE_NAME = 'profile_matches'
             LIMIT 1"
        );

        if ($constraint) {
            Schema::table('match_successes', function (Blueprint $table) use ($constraint): void {
                $table->dropForeign((string) $constraint->CONSTRAINT_NAME);
            });
        }
    }
};
