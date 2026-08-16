<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create profession categories table
        if (!Schema::hasTable('profession_categories')) {
            Schema::create('profession_categories', function (Blueprint $table) {
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

        // Create professions table
        if (!Schema::hasTable('professions')) {
            Schema::create('professions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profession_category_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('profession_category_id');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        // Add foreign key columns to members table for profession system
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'profession_category_id')) {
                $table->foreignId('profession_category_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'profession_id')) {
                $table->foreignId('profession_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('members', 'job_title')) {
                $table->string('job_title')->nullable()->after('profession_id');
            }
            if (!Schema::hasColumn('members', 'organization')) {
                $table->string('organization')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('members', 'years_of_experience')) {
                $table->unsignedTinyInteger('years_of_experience')->nullable()->after('organization');
            }
            // Preserve existing profession field as legacy
            if (Schema::hasColumn('members', 'profession') && !Schema::hasColumn('members', 'profession_legacy')) {
                $table->renameColumn('profession', 'profession_legacy');
            }
        });

        // Add foreign key columns to careers table for profession system
        Schema::table('careers', function (Blueprint $table) {
            if (!Schema::hasColumn('careers', 'profession_category_id')) {
                $table->foreignId('profession_category_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('careers', 'profession_id')) {
                $table->foreignId('profession_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->dropForeign(['profession_category_id']);
            $table->dropForeign(['profession_id']);
            $table->dropColumn(['profession_category_id', 'profession_id']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['profession_category_id']);
            $table->dropForeign(['profession_id']);
            $table->dropColumn(['profession_category_id', 'profession_id', 'job_title', 'organization', 'years_of_experience']);
            if (Schema::hasColumn('members', 'profession_legacy')) {
                $table->renameColumn('profession_legacy', 'profession');
            }
        });

        Schema::dropIfExists('professions');
        Schema::dropIfExists('profession_categories');
    }
};