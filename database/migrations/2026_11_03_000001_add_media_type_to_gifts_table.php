<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-uploaded gift animations can be Lottie JSON, GIF, SVGA or MP4, so the
 * app needs to know which player to use. Null = infer from the file extension.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gifts') && ! Schema::hasColumn('gifts', 'media_type')) {
            Schema::table('gifts', function (Blueprint $table): void {
                $table->string('media_type', 16)->nullable()->after('animated_asset');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gifts', 'media_type')) {
            Schema::table('gifts', function (Blueprint $table): void {
                $table->dropColumn('media_type');
            });
        }
    }
};
