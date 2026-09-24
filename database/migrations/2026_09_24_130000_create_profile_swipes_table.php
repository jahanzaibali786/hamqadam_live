<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Swipe Matching: one row per swipe a member has made, so the deck never shows
 * the same profile twice and a mutual right-swipe can be recognised as a match.
 *
 * The unique key is what makes the endpoint idempotent — swiping the same
 * profile twice (a double tap, a retried request) updates the existing row
 * instead of stacking duplicates that would break the match check.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profile_swipes')) {
            return;
        }

        Schema::create('profile_swipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('swiper_user_id');
            // 'like' = interested, 'pass' = not interested. Kept as a string so
            // future reactions (super-like, block) need no migration.
            $table->string('action', 16)->default('pass');
            $table->unsignedBigInteger('target_user_id');
            $table->timestamps();

            $table->unique(['swiper_user_id', 'target_user_id']);
            $table->index(['swiper_user_id', 'action']);
            // Powers the mutual-match lookup: "did this person already like me?"
            $table->index(['target_user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_swipes');
    }
};
