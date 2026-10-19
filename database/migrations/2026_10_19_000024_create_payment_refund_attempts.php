<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_refund_attempts')) {
            return;
        }

        Schema::create('payment_refund_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('refund_request_id')->unique();
            $table->unsignedBigInteger('package_payment_id')->index();
            $table->string('provider', 30);
            $table->string('idempotency_key', 160)->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status', 40)->default('reserved');
            $table->string('provider_refund_id', 160)->nullable()->unique();
            $table->string('last_error', 255)->nullable();
            $table->dateTime('provider_checked_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'status'], 'refund_attempt_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refund_attempts');
    }
};
