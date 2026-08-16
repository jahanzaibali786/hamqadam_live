<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create institutions table
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('state_id')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->nullable(); // 'University', 'Medical College', 'Engineering College', etc.
                $table->text('description')->nullable();
                $table->string('website')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
                $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                
                $table->index('country_id');
                $table->index('state_id');
                $table->index('city_id');
                $table->index('type');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Note: institution_id foreign keys are already added in the education system migration
        // No additional changes needed here
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};