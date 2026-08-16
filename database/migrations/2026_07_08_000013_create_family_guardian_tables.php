<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_guardian_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->string('status')->default('pending');
            $table->json('permissions')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_user_id', 'guardian_user_id'], 'guardian_profile_unique');
            $table->index(['guardian_user_id', 'status'], 'guardian_status_idx');
        });

        Schema::create('family_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_type');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['guardian_user_id', 'status'], 'family_approval_guardian_idx');
            $table->index(['profile_user_id', 'status'], 'family_approval_profile_idx');
        });

        Schema::create('family_private_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();

            $table->index(['profile_user_id', 'created_at'], 'family_notes_profile_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_private_notes');
        Schema::dropIfExists('family_approval_requests');
        Schema::dropIfExists('family_guardian_links');
    }
};
