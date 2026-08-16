<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('profile_verification_documents');
        Schema::dropIfExists('profile_verification_requests');

        Schema::create('profile_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('status')->default('draft');
            $table->string('cnic_number')->nullable();
            $table->string('face_match_status')->default('pending');
            $table->decimal('face_match_score', 5, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->foreign('user_id', 'pvr_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by', 'pvr_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('profile_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_verification_request_id');
            $table->string('type');
            $table->unsignedBigInteger('upload_id')->nullable();
            $table->string('file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['profile_verification_request_id', 'type'], 'pvd_request_type_idx');
            $table->foreign('profile_verification_request_id', 'pvd_request_fk')
                ->references('id')
                ->on('profile_verification_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_verification_documents');
        Schema::dropIfExists('profile_verification_requests');
    }
};
