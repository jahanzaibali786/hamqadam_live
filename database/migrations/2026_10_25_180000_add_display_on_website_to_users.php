<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // New signups remain opt-in by staff; pre-existing members are enabled below.
            $table->boolean('display_on_website')->default(false)->index();
        });

        // Existing premium, free and basic member records should start checked.
        // The homepage query still excludes blocked, unapproved and private profiles.
        DB::table('users')->where('user_type', 'member')
            ->update(['display_on_website' => true]);

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'introduction')) {
            Schema::table('members', function (Blueprint $table) {
                $table->text('introduction')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('display_on_website');
        });
        // Deliberately do not shrink introduction back to VARCHAR(255):
        // that would truncate bios longer than 255 characters.
    }
};
