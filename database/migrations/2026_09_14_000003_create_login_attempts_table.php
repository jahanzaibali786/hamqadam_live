<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_attempts')) {
            return;
        }

        Schema::create('login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('identifier_hash', 64)->index();
            $table->string('identifier_hint')->nullable();
            $table->string('channel', 30)->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('successful')->default(false)->index();
            $table->string('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['identifier_hash', 'ip_address', 'occurred_at'], 'login_attempts_security_window_idx');
            $table->index(['user_id', 'successful', 'occurred_at'], 'login_attempts_user_success_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};