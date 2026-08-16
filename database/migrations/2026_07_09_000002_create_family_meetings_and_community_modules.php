<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('family_guardian_links')) {
            Schema::table('family_guardian_links', function (Blueprint $table) {
                if (! Schema::hasColumn('family_guardian_links', 'guardian_role')) {
                    $table->string('guardian_role')->nullable()->after('relationship');
                }
                if (! Schema::hasColumn('family_guardian_links', 'is_wali')) {
                    $table->boolean('is_wali')->default(false)->after('guardian_role');
                }
                if (! Schema::hasColumn('family_guardian_links', 'digest_frequency')) {
                    $table->string('digest_frequency')->default('weekly')->after('permissions');
                }
                if (! Schema::hasColumn('family_guardian_links', 'last_digest_sent_at')) {
                    $table->timestamp('last_digest_sent_at')->nullable()->after('approved_at');
                }
            });
        }

        if (Schema::hasTable('members') && ! Schema::hasColumn('members', 'wali_mode_enabled')) {
            Schema::table('members', function (Blueprint $table) {
                $table->boolean('wali_mode_enabled')->default(false);
            });
        }

        if (! Schema::hasTable('family_conversations')) {
            Schema::create('family_conversations', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('proposal_id')->nullable();
                $table->foreignId('first_profile_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('second_profile_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('active');
                $table->timestamps();

                $table->index(['first_profile_user_id', 'second_profile_user_id'], 'family_conversation_profiles_idx');
                $table->foreign('proposal_id')->references('id')->on('express_interests')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('family_conversation_messages')) {
            Schema::create('family_conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('family_conversation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->json('attachments')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['family_conversation_id', 'created_at'], 'family_conversation_messages_idx');
            });
        }

        if (! Schema::hasTable('proposal_meetings')) {
            Schema::create('proposal_meetings', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('express_interest_id');
                $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('chaperone_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('meeting_type')->default('virtual');
                $table->string('status')->default('scheduled');
                $table->timestamp('scheduled_at');
                $table->unsignedSmallInteger('duration_minutes')->default(30);
                $table->string('meeting_url')->nullable();
                $table->string('location')->nullable();
                $table->boolean('chaperone_mode')->default(false);
                $table->boolean('recording_consent_requested')->default(false);
                $table->string('recording_consent_status')->default('not_requested');
                $table->string('recording_url')->nullable();
                $table->json('pre_meeting_questionnaire')->nullable();
                $table->json('post_meeting_feedback')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['express_interest_id', 'scheduled_at'], 'proposal_meetings_proposal_idx');
                $table->index(['status', 'scheduled_at'], 'proposal_meetings_status_idx');
                $table->foreign('express_interest_id')->references('id')->on('express_interests')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('relationship_status_updates')) {
            Schema::create('relationship_status_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('partner_user_id')->constrained('users')->cascadeOnDelete();
                $table->bigInteger('express_interest_id')->nullable();
                $table->string('status');
                $table->date('status_date')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_public')->default(false);
                $table->string('moderation_status')->default('pending');
                $table->timestamps();

                $table->index(['user_id', 'status'], 'relationship_updates_user_idx');
                $table->foreign('express_interest_id')->references('id')->on('express_interests')->nullOnDelete();
            });
        }

        Schema::create('expert_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('relationship');
            $table->string('question');
            $table->text('details')->nullable();
            $table->text('answer')->nullable();
            $table->string('expert_name')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();

            $table->index(['status', 'created_at'], 'expert_questions_status_idx');
        });

        Schema::create('community_forums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('community_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_forum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('moderation_status')->default('pending');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['community_forum_id', 'moderation_status'], 'community_threads_forum_idx');
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('moderation_status')->default('pending');
            $table->timestamps();

            $table->index(['community_thread_id', 'created_at'], 'community_posts_thread_idx');
        });

        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('host_name')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['status', 'starts_at'], 'webinars_status_idx');
        });

        Schema::create('webinar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['webinar_id', 'user_id'], 'webinar_user_unique');
        });

        Schema::create('marriage_tips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('category')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
        });

        Schema::create('regional_updates', function (Blueprint $table) {
            $table->id();
            $table->string('region')->nullable();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_updates');
        Schema::dropIfExists('marriage_tips');
        Schema::dropIfExists('webinar_registrations');
        Schema::dropIfExists('webinars');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('community_threads');
        Schema::dropIfExists('community_forums');
        Schema::dropIfExists('expert_questions');
        Schema::dropIfExists('relationship_status_updates');
        Schema::dropIfExists('proposal_meetings');
        Schema::dropIfExists('family_conversation_messages');
        Schema::dropIfExists('family_conversations');

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'wali_mode_enabled')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('wali_mode_enabled');
            });
        }

        if (Schema::hasTable('family_guardian_links')) {
            Schema::table('family_guardian_links', function (Blueprint $table) {
                foreach (['guardian_role', 'is_wali', 'digest_frequency', 'last_digest_sent_at'] as $column) {
                    if (Schema::hasColumn('family_guardian_links', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
