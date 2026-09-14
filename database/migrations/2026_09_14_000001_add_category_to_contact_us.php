<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_us') && ! Schema::hasColumn('contact_us', 'category')) {
            Schema::table('contact_us', function (Blueprint $table): void {
                $table->string('category', 30)
                    ->nullable()
                    ->after('subject')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contact_us') && Schema::hasColumn('contact_us', 'category')) {
            Schema::table('contact_us', function (Blueprint $table): void {
                $table->dropColumn('category');
            });
        }
    }
};
