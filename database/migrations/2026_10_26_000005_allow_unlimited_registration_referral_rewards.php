<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reward_rules')) {
            return;
        }

        Schema::table('reward_rules', function (Blueprint $table): void {
            // -1 represents an unlimited reward cap for registration referrals.
            $table->integer('max_per_user')->default(25)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reward_rules')) {
            return;
        }

        Schema::table('reward_rules', function (Blueprint $table): void {
            $table->unsignedInteger('max_per_user')->default(25)->change();
        });
    }
};
