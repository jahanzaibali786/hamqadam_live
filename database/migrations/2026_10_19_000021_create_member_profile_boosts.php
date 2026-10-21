<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_profile_boosts')) return;
        Schema::create('member_profile_boosts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->index(['user_id','starts_at']);
            $table->index('ends_at');
        });
    }
    public function down(): void { Schema::dropIfExists('member_profile_boosts'); }
};
