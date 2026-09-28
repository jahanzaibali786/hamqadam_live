<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Welcome bonus (Task: "first fully-verified members claim 25 free coins").
 *
 * The claim is once-per-account and only allowed while
 * `verification_status = 'verified'` (or the AI path approved it), so the two
 * columns give the service its idempotency guard and the app its claim state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'welcome_bonus_claimed_at')) {
                $table->timestamp('welcome_bonus_claimed_at')->nullable()->after('remaining_interest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'welcome_bonus_claimed_at')) {
                $table->dropColumn('welcome_bonus_claimed_at');
            }
        });
    }
};
