<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || Schema::hasColumn('notifications', 'category')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('category', 40)->nullable()->after('type')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'category')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropColumn('category');
            });
        }
    }
};
