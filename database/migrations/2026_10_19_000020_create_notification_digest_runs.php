<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_digest_runs')) return;
        Schema::create('notification_digest_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('frequency', 12);
            $table->date('period_date');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('notification_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'frequency', 'period_date'], 'digest_runs_user_period_unique');
            $table->index(['status', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('notification_digest_runs');
    }
};
