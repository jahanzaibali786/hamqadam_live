<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->string('agora_channel')->unique();
            $table->string('call_type', 20);
            $table->string('status', 20)->index();
            $table->timestamp('ring_expires_at')->nullable()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('answered_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status'], 'calls_conversation_status_idx');
            $table->index(['caller_id', 'status'], 'calls_caller_status_idx');
            $table->index(['receiver_id', 'status'], 'calls_receiver_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
