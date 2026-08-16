<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'action_type'], 'safety_target_type_idx');
            $table->index(['actor_user_id', 'action_type'], 'safety_actor_type_idx');
        });

        Schema::create('moderation_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_type')->default('user_report');
            $table->string('status')->default('open');
            $table->string('severity')->default('medium');
            $table->text('reason')->nullable();
            $table->json('evidence')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity'], 'moderation_status_severity_idx');
            $table->index(['reported_user_id', 'status'], 'moderation_reported_status_idx');
        });

        Schema::create('suspicious_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type');
            $table->string('risk_level')->default('low');
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->json('signals')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['risk_level', 'created_at'], 'suspicious_risk_created_idx');
            $table->index(['user_id', 'activity_type'], 'suspicious_user_type_idx');
        });

        Schema::create('ai_feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature');
            $table->text('prompt')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('provider')->default('local');
            $table->string('status')->default('completed');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'feature'], 'ai_user_feature_idx');
            $table->index(['feature', 'created_at'], 'ai_feature_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feature_requests');
        Schema::dropIfExists('suspicious_activity_logs');
        Schema::dropIfExists('moderation_cases');
        Schema::dropIfExists('safety_actions');
    }
};
