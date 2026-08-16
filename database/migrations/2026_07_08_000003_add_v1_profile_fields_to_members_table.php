<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'video_introduction')) {
                $table->string('video_introduction')->nullable()->after('introduction');
            }

            if (! Schema::hasColumn('members', 'voice_introduction')) {
                $table->string('voice_introduction')->nullable()->after('video_introduction');
            }

            if (! Schema::hasColumn('members', 'ai_generated_bio')) {
                $table->text('ai_generated_bio')->nullable()->after('voice_introduction');
            }

            if (! Schema::hasColumn('members', 'travel_preferences')) {
                $table->text('travel_preferences')->nullable()->after('known_languages');
            }

            if (! Schema::hasColumn('members', 'future_goals')) {
                $table->text('future_goals')->nullable()->after('travel_preferences');
            }

            if (! Schema::hasColumn('members', 'profile_completion_percentage')) {
                $table->unsignedTinyInteger('profile_completion_percentage')->default(0)->after('future_goals');
            }

            if (! Schema::hasColumn('members', 'hide_profile')) {
                $table->boolean('hide_profile')->default(false)->after('profile_completion_percentage');
            }

            if (! Schema::hasColumn('members', 'verification_status')) {
                $table->string('verification_status', 30)->default('unverified')->after('hide_profile');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            foreach ([
                'video_introduction',
                'voice_introduction',
                'ai_generated_bio',
                'travel_preferences',
                'future_goals',
                'profile_completion_percentage',
                'hide_profile',
                'verification_status',
            ] as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

