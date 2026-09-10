<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'ai_verification_reason')) {
                $table->text('ai_verification_reason')->nullable()->after('ai_verification_recommendation');
            }
        });

        Schema::table('ai_verification_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_verification_attempts', 'review_reason')) {
                $table->text('review_reason')->nullable()->after('recommendation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'ai_verification_reason')) {
                $table->dropColumn('ai_verification_reason');
            }
        });

        Schema::table('ai_verification_attempts', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_verification_attempts', 'review_reason')) {
                $table->dropColumn('review_reason');
            }
        });
    }
};
