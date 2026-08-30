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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_identifier')) {
                $table->string('admin_identifier', 50)->nullable()->default('member')->after('user_type');
            }
        });

        if (Schema::hasColumn('users', 'admin_identifier')) {
            DB::table('users')->where('user_type', 'admin')->update(['admin_identifier' => 'admin']);
            DB::table('users')->where('user_type', 'staff')->update(['admin_identifier' => 'staff']);
            DB::table('users')->where('user_type', 'subadmin')->update(['admin_identifier' => 'subadmin']);
            DB::table('users')->whereNull('admin_identifier')->update(['admin_identifier' => 'member']);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(
                ['name' => 'Sub Admin', 'guard_name' => 'web'],
                [
                    'name' => 'Sub Admin',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_identifier')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_identifier');
            });
        }
    }
};
