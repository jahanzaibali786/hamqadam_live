<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('workflow_message_templates')) {
            Schema::create('workflow_message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 16);
                $table->string('language', 12)->default('en');
                $table->string('title', 150);
                $table->text('opening');
                $table->text('body');
                $table->text('closing');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['kind','language','is_active','display_order'], 'workflow_template_pick_idx');
            });
        }
        if (!Schema::hasColumn('express_interests', 'workflow_template_id')) {
            Schema::table('express_interests', function (Blueprint $table) {
                $table->foreignId('workflow_template_id')->nullable()->constrained('workflow_message_templates')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('express_interests', 'workflow_reminded_at')) {
            Schema::table('express_interests', function (Blueprint $table) {
                $table->timestamp('workflow_reminded_at')->nullable();
            });
        }
        if (!Schema::hasTable('marriage_proposals')) {
            Schema::create('marriage_proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interest_id')->constrained('express_interests')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('workflow_message_templates')->nullOnDelete();
                $table->string('language',12)->default('en');
                $table->string('source',16)->default('static');
                $table->longText('message');
                $table->string('status',16)->default('draft');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('reminded_at')->nullable();
                $table->string('family_stage',24)->nullable();
                $table->timestamps();
                $table->index(['sender_id','recipient_id','status'], 'marriage_proposal_pair_status');
                $table->index(['status','created_at'], 'marriage_proposal_reminders');
            });
        }
        if (!Schema::hasTable('member_communication_preferences')) {
            Schema::create('member_communication_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->boolean('allow_audio_calls')->default(true);
                $table->boolean('allow_video_calls')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_communication_preferences');
        Schema::dropIfExists('marriage_proposals');
        if (Schema::hasColumn('express_interests', 'workflow_reminded_at')) {
            Schema::table('express_interests', fn (Blueprint $table) => $table->dropColumn('workflow_reminded_at'));
        }
        if (Schema::hasColumn('express_interests', 'workflow_template_id')) {
            Schema::table('express_interests', fn (Blueprint $table) => $table->dropConstrainedForeignId('workflow_template_id'));
        }
        Schema::dropIfExists('workflow_message_templates');
    }
};
