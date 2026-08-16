<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create main sects table
        if (!Schema::hasTable('sect_main')) {
            Schema::create('sect_main', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('religion_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('religion_id')->references('id')->on('religions')->onDelete('set null');
                
                $table->index('religion_id');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Create schools of thought table
        if (!Schema::hasTable('school_of_thought')) {
            Schema::create('school_of_thought', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sect_main_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('sect_main_id')->references('id')->on('sect_main')->onDelete('set null');
                
                $table->index('sect_main_id');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Create traditions table (for Sunni traditions like Barelvi, Deobandi, etc.)
        if (!Schema::hasTable('traditions')) {
            Schema::create('traditions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_of_thought_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('school_of_thought_id')->references('id')->on('school_of_thought')->onDelete('set null');
                
                $table->index('school_of_thought_id');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Add foreign key columns to spiritual_backgrounds table
        Schema::table('spiritual_backgrounds', function (Blueprint $table) {
            if (!Schema::hasColumn('spiritual_backgrounds', 'sect_main_id')) {
                $table->unsignedBigInteger('sect_main_id')->nullable();
                $table->foreign('sect_main_id')->references('id')->on('sect_main')->onDelete('set null');
            }
            if (!Schema::hasColumn('spiritual_backgrounds', 'school_of_thought_id')) {
                $table->unsignedBigInteger('school_of_thought_id')->nullable();
                $table->foreign('school_of_thought_id')->references('id')->on('school_of_thought')->onDelete('set null');
            }
            if (!Schema::hasColumn('spiritual_backgrounds', 'tradition_id')) {
                $table->unsignedBigInteger('tradition_id')->nullable();
                $table->foreign('tradition_id')->references('id')->on('traditions')->onDelete('set null');
            }
        });

        // Add foreign key columns to members table for easier access
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'sect_main_id')) {
                $table->unsignedBigInteger('sect_main_id')->nullable();
                $table->foreign('sect_main_id')->references('id')->on('sect_main')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'school_of_thought_id')) {
                $table->unsignedBigInteger('school_of_thought_id')->nullable();
                $table->foreign('school_of_thought_id')->references('id')->on('school_of_thought')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'tradition_id')) {
                $table->unsignedBigInteger('tradition_id')->nullable();
                $table->foreign('tradition_id')->references('id')->on('traditions')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['sect_main_id']);
            $table->dropForeign(['school_of_thought_id']);
            $table->dropForeign(['tradition_id']);
            $table->dropColumn(['sect_main_id', 'school_of_thought_id', 'tradition_id']);
        });

        Schema::table('spiritual_backgrounds', function (Blueprint $table) {
            $table->dropForeign(['sect_main_id']);
            $table->dropForeign(['school_of_thought_id']);
            $table->dropForeign(['tradition_id']);
            $table->dropColumn(['sect_main_id', 'school_of_thought_id', 'tradition_id']);
        });

        Schema::dropIfExists('traditions');
        Schema::dropIfExists('school_of_thought');
        Schema::dropIfExists('sect_main');
    }
};