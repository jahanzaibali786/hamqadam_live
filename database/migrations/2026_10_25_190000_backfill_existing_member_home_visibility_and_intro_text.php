<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PR #131's migration is already merged and may already be recorded in the
 * migrations table. This one-time follow-up applies the same backfill/schema
 * correction on installations where the older migration has already run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'display_on_website')) {
            DB::table('users')->where('user_type', 'member')
                ->update(['display_on_website' => true]);
        }

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'introduction')) {
            Schema::table('members', function (Blueprint $table) {
                $table->text('introduction')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // No reversal: shrinking TEXT or reversing admin selections would
        // destroy user content or overwrite subsequent moderator decisions.
    }
};
