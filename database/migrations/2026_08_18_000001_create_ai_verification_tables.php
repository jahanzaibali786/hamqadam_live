<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| AI identity verification bookkeeping.
|
| Deliberately a SEPARATE table rather than reusing profile_verification_requests:
| VerificationService::submit() throws a 409 when a non-final request already
| exists, and only Approved/Rejected are final. Creating a draft request at
| registration time would therefore lock the user out of ever submitting their
| CNIC documents.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_verification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            // Which flow triggered this: registration_api, registration_web,
            // document_submit, manual_retry, admin.
            $table->string('source', 40)->index();
            // Set when the attempt belongs to a document submission.
            $table->unsignedBigInteger('profile_verification_request_id')->nullable();
            // The verification_id we sent to the model; it echoes it back and
            // uses it as the log correlation id, so keep it for tracing.
            $table->string('verification_id', 100)->nullable()->index();

            // pending | completed | failed | skipped
            $table->string('status', 20)->default('pending')->index();
            // APPROVE | REJECT | MANUAL_REVIEW
            $table->string('recommendation', 30)->nullable();
            $table->decimal('identity_confidence_score', 5, 2)->nullable();
            $table->decimal('fraud_risk_score', 5, 2)->nullable();
            $table->string('fraud_risk_level', 20)->nullable();
            $table->boolean('face_detected')->nullable();

            // Which images we were actually able to send.
            $table->json('images_sent')->nullable();
            // Full model response, for audit. No image bytes are stored.
            $table->longText('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code', 60)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->foreign('user_id', 'ava_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        // Summary on members so the dashboard and API can answer
        // "is this user AI-verified?" without touching the attempts table.
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'ai_verification_status')) {
                // not_started | pending | approved | rejected | manual_review | failed
                $table->string('ai_verification_status', 30)->default('not_started')->index();
            }
            if (! Schema::hasColumn('members', 'ai_verification_recommendation')) {
                $table->string('ai_verification_recommendation', 30)->nullable();
            }
            if (! Schema::hasColumn('members', 'ai_verification_attempts')) {
                $table->unsignedSmallInteger('ai_verification_attempts')->default(0);
            }
            if (! Schema::hasColumn('members', 'ai_verification_last_attempt_at')) {
                $table->timestamp('ai_verification_last_attempt_at')->nullable();
            }
            if (! Schema::hasColumn('members', 'ai_verified_at')) {
                $table->timestamp('ai_verified_at')->nullable();
            }
        });

        // The AI result for a document submission belongs on the request too.
        // face_match_status / face_match_score already exist on this table and
        // were clearly intended for exactly this - populate them rather than
        // adding duplicates.
        Schema::table('profile_verification_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_verification_requests', 'ai_recommendation')) {
                $table->string('ai_recommendation', 30)->nullable()->after('face_match_score');
            }
            if (! Schema::hasColumn('profile_verification_requests', 'ai_fraud_risk_score')) {
                $table->decimal('ai_fraud_risk_score', 5, 2)->nullable()->after('ai_recommendation');
            }
            if (! Schema::hasColumn('profile_verification_requests', 'ai_checked_at')) {
                $table->timestamp('ai_checked_at')->nullable()->after('ai_fraud_risk_score');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_verification_attempts');

        Schema::table('members', function (Blueprint $table) {
            foreach ([
                'ai_verification_status',
                'ai_verification_recommendation',
                'ai_verification_attempts',
                'ai_verification_last_attempt_at',
                'ai_verified_at',
            ] as $col) {
                if (Schema::hasColumn('members', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('profile_verification_requests', function (Blueprint $table) {
            foreach (['ai_recommendation', 'ai_fraud_risk_score', 'ai_checked_at'] as $col) {
                if (Schema::hasColumn('profile_verification_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
