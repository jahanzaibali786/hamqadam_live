<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('match_outcome_reviews')) {
            Schema::create('match_outcome_reviews', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('member_a_id');
                $table->unsignedBigInteger('member_b_id');
                $table->string('status', 20)->default('pending');
                $table->string('verification_method', 30)->nullable();
                $table->unsignedBigInteger('happy_story_id')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('internal_notes')->nullable();
                $table->timestamps();
                $table->unique(['member_a_id','member_b_id'],'match_outcome_pair_unique');
                $table->index(['status','reviewed_at'],'match_outcome_review_status_idx');
            });
        }
        if (! Schema::hasTable('match_outcome_review_events')) {
            Schema::create('match_outcome_review_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('match_outcome_review_id');
                $table->unsignedBigInteger('actor_id');
                $table->string('previous_status', 20)->nullable();
                $table->string('new_status', 20);
                $table->string('verification_method', 30)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['match_outcome_review_id','created_at'],'outcome_review_audit_idx');
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('match_outcome_review_events');
        Schema::dropIfExists('match_outcome_reviews');
    }
};
