<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create education levels table
        if (!Schema::hasTable('education_levels')) {
            Schema::create('education_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Create degrees table
        if (!Schema::hasTable('degrees')) {
            Schema::create('degrees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('education_level_id')->nullable()->constrained()->onDelete('set null');
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('category')->nullable(); // e.g., 'Computer Science', 'Engineering', 'Medical'
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('education_level_id');
                $table->index('category');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Create fields of study table
        if (!Schema::hasTable('fields_of_study')) {
            Schema::create('fields_of_study', function (Blueprint $table) {
                $table->id();
                $table->foreignId('degree_id')->nullable()->constrained()->onDelete('set null');
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('degree_id');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Add foreign key columns to members table for education system
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->constrained('education_levels')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'degree_id')) {
                $table->foreignId('degree_id')->nullable()->constrained('degrees')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'field_of_study_id')) {
                $table->foreignId('field_of_study_id')->nullable()->constrained('fields_of_study')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'graduation_year')) {
                $table->unsignedSmallInteger('graduation_year')->nullable();
            }
            if (!Schema::hasColumn('members', 'education_status')) {
                $table->string('education_status', 20)->nullable(); // 'completed', 'in_progress', 'dropped'
            }
            if (!Schema::hasColumn('members', 'expected_graduation_year')) {
                $table->unsignedSmallInteger('expected_graduation_year')->nullable();
            }
        });

        // Add foreign key columns to education table for education system
        Schema::table('education', function (Blueprint $table) {
            if (!Schema::hasColumn('education', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->constrained('education_levels')->onDelete('set null');
            }
            if (!Schema::hasColumn('education', 'degree_id')) {
                $table->foreignId('degree_id')->nullable()->constrained('degrees')->onDelete('set null');
            }
            if (!Schema::hasColumn('education', 'field_of_study_id')) {
                $table->foreignId('field_of_study_id')->nullable()->constrained('fields_of_study')->onDelete('set null');
            }
            if (!Schema::hasColumn('education', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            }
            if (!Schema::hasColumn('education', 'graduation_year')) {
                $table->unsignedSmallInteger('graduation_year')->nullable();
            }
            if (!Schema::hasColumn('education', 'education_status')) {
                $table->string('education_status', 20)->nullable();
            }
            if (!Schema::hasColumn('education', 'expected_graduation_year')) {
                $table->unsignedSmallInteger('expected_graduation_year')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('education', function (Blueprint $table) {
            $table->dropForeign(['education_level_id']);
            $table->dropForeign(['degree_id']);
            $table->dropForeign(['field_of_study_id']);
            $table->dropForeign(['institution_id']);
            $table->dropColumn(['education_level_id', 'degree_id', 'field_of_study_id', 'institution_id', 'graduation_year', 'education_status', 'expected_graduation_year']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['education_level_id']);
            $table->dropForeign(['degree_id']);
            $table->dropForeign(['field_of_study_id']);
            $table->dropForeign(['institution_id']);
            $table->dropColumn(['education_level_id', 'degree_id', 'field_of_study_id', 'institution_id', 'graduation_year', 'education_status', 'expected_graduation_year']);
        });

        Schema::dropIfExists('fields_of_study');
        Schema::dropIfExists('degrees');
        Schema::dropIfExists('education_levels');
    }
};