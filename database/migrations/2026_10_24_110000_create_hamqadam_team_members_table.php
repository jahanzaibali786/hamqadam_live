<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('hamqadam_team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('initials', 8)->nullable();
            $table->string('role', 180);
            $table->string('badge', 100)->nullable();
            $table->string('group', 40)->default('Family');
            $table->text('bio')->nullable();
            $table->text('focus')->nullable();
            $table->string('location', 120)->nullable();
            $table->string('action_text', 80)->nullable();
            $table->string('action_link', 512)->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('hamqadam_team_members'); }
};
