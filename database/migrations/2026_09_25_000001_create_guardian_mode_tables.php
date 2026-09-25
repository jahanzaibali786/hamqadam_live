<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardian Mode hardening, additive to the existing family tables:
 *
 *  - guardian_invitations   — single-use, expiring, non-guessable tokens (§6)
 *  - guardian_permissions   — granular permission keys per guardian (§7)
 *  - guardian_feedback      — shortlist / not-suitable feedback (§11)
 *  - guardian_activity_logs — readable audit trail (§20)
 *  - family_introductions   — the controlled family-introduction stage (§15)
 *
 *  - family_guardian_links gets pause/revocation lifecycle columns (§25)
 *  - family_private_notes gets visibility (§11: Guardian Only / Primary User +
 *    Guardian / Authorized Family Context)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Lifecycle columns on the existing link table ────────────────────
        if (Schema::hasTable('family_guardian_links')) {
            Schema::table('family_guardian_links', function (Blueprint $table): void {
                if (! Schema::hasColumn('family_guardian_links', 'invited_at')) {
                    $table->timestamp('invited_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('family_guardian_links', 'paused_at')) {
                    $table->timestamp('paused_at')->nullable()->after('approved_at');
                }
                if (! Schema::hasColumn('family_guardian_links', 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable()->after('paused_at');
                }
                if (! Schema::hasColumn('family_guardian_links', 'last_active_at')) {
                    $table->timestamp('last_active_at')->nullable()->after('revoked_at');
                }
            });
        }

        // ── Invitations: secure, one-time, expiring ─────────────────────────
        if (! Schema::hasTable('guardian_invitations')) {
            Schema::create('guardian_invitations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('contact')->nullable(); // phone or email the invite was sent to
                $table->string('relationship')->nullable();
                $table->string('guardian_role')->nullable();
                $table->boolean('is_wali')->default(false);
                $table->json('permissions')->nullable();
                $table->string('token', 64)->unique(); // 48 random bytes, hex = 96 — trimmed to 64 for safety
                $table->string('status')->default('pending'); // pending|accepted|expired|revoked
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamps();

                $table->index(['profile_user_id', 'status'], 'guardian_invite_profile_idx');
                $table->index(['status', 'expires_at'], 'guardian_invite_expiry_idx');
            });
        }

        // ── Granular permissions ────────────────────────────────────────────
        if (! Schema::hasTable('guardian_permissions')) {
            Schema::create('guardian_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('guardian_link_id')->constrained('family_guardian_links')->cascadeOnDelete();
                $table->string('permission_key', 64);
                $table->boolean('is_allowed')->default(false);
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['guardian_link_id', 'permission_key'], 'guardian_permission_unique');
            });
        }

        // ── Guardian feedback on matches ────────────────────────────────────
        if (! Schema::hasTable('guardian_feedback')) {
            Schema::create('guardian_feedback', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('guardian_link_id')->constrained('family_guardian_links')->cascadeOnDelete();
                $table->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('feedback_type'); // shortlist|not_suitable|recommend
                $table->string('reason')->nullable(); // §11 fixed reasons
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->index(['profile_user_id', 'target_user_id'], 'guardian_feedback_target_idx');
                $table->unique(
                    ['guardian_link_id', 'target_user_id', 'feedback_type'],
                    'guardian_feedback_unique'
                );
            });
        }

        // ── Audit log ───────────────────────────────────────────────────────
        if (! Schema::hasTable('guardian_activity_logs')) {
            Schema::create('guardian_activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('guardian_link_id')->nullable()->constrained('family_guardian_links')->nullOnDelete();
                $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 64);
                $table->string('resource_type', 64)->nullable();
                $table->unsignedBigInteger('resource_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['profile_user_id', 'created_at'], 'guardian_activity_profile_idx');
            });
        }

        // ── Family introductions ────────────────────────────────────────────
        if (! Schema::hasTable('family_introductions')) {
            Schema::create('family_introductions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('proposal_id')->constrained('express_interests')->cascadeOnDelete();
                $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('requested'); // requested|accepted|declined|active|completed|cancelled
                $table->foreignId('first_guardian_link_id')->nullable()->constrained('family_guardian_links')->nullOnDelete();
                $table->foreignId('second_guardian_link_id')->nullable()->constrained('family_guardian_links')->nullOnDelete();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();

                $table->index(['proposal_id', 'status'], 'family_intro_proposal_idx');
            });
        }

        // ── Note visibility (§11) ───────────────────────────────────────────
        if (Schema::hasTable('family_private_notes') && ! Schema::hasColumn('family_private_notes', 'visibility')) {
            Schema::table('family_private_notes', function (Blueprint $table): void {
                $table->string('visibility')->default('primary_and_guardian')->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('family_private_notes') && Schema::hasColumn('family_private_notes', 'visibility')) {
            Schema::table('family_private_notes', function (Blueprint $table): void {
                $table->dropColumn('visibility');
            });
        }

        Schema::dropIfExists('family_introductions');
        Schema::dropIfExists('guardian_activity_logs');
        Schema::dropIfExists('guardian_feedback');
        Schema::dropIfExists('guardian_permissions');
        Schema::dropIfExists('guardian_invitations');

        if (Schema::hasTable('family_guardian_links')) {
            Schema::table('family_guardian_links', function (Blueprint $table): void {
                foreach (['invited_at', 'paused_at', 'revoked_at', 'last_active_at'] as $column) {
                    if (Schema::hasColumn('family_guardian_links', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
