<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advisor_consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 200);
            $table->string('phone', 35);
            $table->string('city', 120);
            $table->string('advisor', 150);
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->string('notification_status', 20)->default('pending');
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_consultation_requests');
    }
};
