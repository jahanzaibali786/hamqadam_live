<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_refund_requests')) return;
        Schema::create('payment_refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_payment_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 40)->default('requested');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PKR');
            $table->text('reason');
            $table->string('provider_refund_reference')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refund_requests');
    }
};
