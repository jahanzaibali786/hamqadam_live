<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Step 2 - Basic Profile
            if (! Schema::hasColumn('members', 'disability')) {
                $table->string('disability', 255)->nullable()->after('children');
            }

            // Step 3 - About Me & Personality (after introduction)
            if (! Schema::hasColumn('members', 'looking_for')) {
                $table->text('looking_for')->nullable()->after('introduction');
            }
            if (! Schema::hasColumn('members', 'life_values')) {
                $table->json('life_values')->nullable()->after('looking_for');
            }
            if (! Schema::hasColumn('members', 'personality_type')) {
                $table->string('personality_type', 50)->nullable()->after('life_values');
            }
            if (! Schema::hasColumn('members', 'communication_style')) {
                $table->string('communication_style', 50)->nullable()->after('personality_type');
            }
            if (! Schema::hasColumn('members', 'love_language')) {
                $table->json('love_language')->nullable()->after('communication_style');
            }
            if (! Schema::hasColumn('members', 'conflict_resolution_style')) {
                $table->string('conflict_resolution_style', 50)->nullable()->after('love_language');
            }

            // Step 4 - Religion & Culture (after personal_value is NOT on members — use known_languages)
            if (! Schema::hasColumn('members', 'religious_practice_level')) {
                $table->string('religious_practice_level', 50)->nullable()->after('known_languages');
            }
            if (! Schema::hasColumn('members', 'prayer_frequency')) {
                $table->string('prayer_frequency', 50)->nullable()->after('religious_practice_level');
            }
            if (! Schema::hasColumn('members', 'community_biradari')) {
                $table->string('community_biradari', 100)->nullable()->after('prayer_frequency');
            }
            if (! Schema::hasColumn('members', 'hijab_beard_preference')) {
                $table->string('hijab_beard_preference', 100)->nullable()->after('community_biradari');
            }

            // Step 5 - Education & Career
            if (! Schema::hasColumn('members', 'education_level')) {
                $table->string('education_level', 100)->nullable()->after('hijab_beard_preference');
            }
            if (! Schema::hasColumn('members', 'employment_status')) {
                $table->string('employment_status', 20)->nullable()->after('education_level');
            }
            if (! Schema::hasColumn('members', 'work_location_city')) {
                $table->string('work_location_city', 255)->nullable()->after('employment_status');
            }
            if (! Schema::hasColumn('members', 'annual_income')) {
                $table->decimal('annual_income', 12, 2)->nullable()->after('work_location_city');
            }

            // Step 6 - Family Details
            if (! Schema::hasColumn('members', 'father_occupation')) {
                $table->string('father_occupation', 255)->nullable()->after('annual_income');
            }
            if (! Schema::hasColumn('members', 'mother_occupation')) {
                $table->string('mother_occupation', 255)->nullable()->after('father_occupation');
            }
            if (! Schema::hasColumn('members', 'family_type')) {
                $table->string('family_type', 50)->nullable()->after('mother_occupation');
            }
            if (! Schema::hasColumn('members', 'siblings_brothers')) {
                $table->unsignedTinyInteger('siblings_brothers')->nullable()->after('family_type');
            }
            if (! Schema::hasColumn('members', 'siblings_sisters')) {
                $table->unsignedTinyInteger('siblings_sisters')->nullable()->after('siblings_brothers');
            }
            if (! Schema::hasColumn('members', 'married_siblings')) {
                $table->unsignedTinyInteger('married_siblings')->nullable()->after('siblings_sisters');
            }
            if (! Schema::hasColumn('members', 'family_location')) {
                $table->string('family_location', 255)->nullable()->after('married_siblings');
            }
            if (! Schema::hasColumn('members', 'guardian_name')) {
                $table->string('guardian_name', 255)->nullable()->after('family_location');
            }
            if (! Schema::hasColumn('members', 'guardian_contact')) {
                $table->string('guardian_contact', 100)->nullable()->after('guardian_name');
            }
            if (! Schema::hasColumn('members', 'family_values')) {
                $table->string('family_values', 50)->nullable()->after('guardian_contact');
            }
            if (! Schema::hasColumn('members', 'family_bio')) {
                $table->text('family_bio')->nullable()->after('family_values');
            }
            if (! Schema::hasColumn('members', 'family_expectations')) {
                $table->text('family_expectations')->nullable()->after('family_bio');
            }
            if (! Schema::hasColumn('members', 'parents_contact')) {
                $table->string('parents_contact', 100)->nullable()->after('family_expectations');
            }

            // Step 7 - Marriage & Future Plans
            if (! Schema::hasColumn('members', 'children_preference')) {
                $table->string('children_preference', 50)->nullable()->after('parents_contact');
            }
            if (! Schema::hasColumn('members', 'relocation_preference')) {
                $table->string('relocation_preference', 50)->nullable()->after('children_preference');
            }
            if (! Schema::hasColumn('members', 'visa_immigration_status')) {
                $table->string('visa_immigration_status', 50)->nullable()->after('relocation_preference');
            }
            if (! Schema::hasColumn('members', 'future_living_preference')) {
                $table->string('future_living_preference', 50)->nullable()->after('visa_immigration_status');
            }
            if (! Schema::hasColumn('members', 'financial_responsibility')) {
                $table->string('financial_responsibility', 50)->nullable()->after('future_living_preference');
            }
            if (! Schema::hasColumn('members', 'marriage_timeline')) {
                $table->string('marriage_timeline', 50)->nullable()->after('financial_responsibility');
            }
            if (! Schema::hasColumn('members', 'expectations_after_marriage')) {
                $table->json('expectations_after_marriage')->nullable()->after('marriage_timeline');
            }
            if (! Schema::hasColumn('members', 'willing_to_work_after_marriage')) {
                $table->boolean('willing_to_work_after_marriage')->nullable()->after('expectations_after_marriage');
            }
            if (! Schema::hasColumn('members', 'expects_spouse_to_work')) {
                $table->boolean('expects_spouse_to_work')->nullable()->after('willing_to_work_after_marriage');
            }

            // Step 8 - Lifestyle & Interests
            if (! Schema::hasColumn('members', 'hobbies')) {
                $table->text('hobbies')->nullable()->after('expects_spouse_to_work');
            }
            if (! Schema::hasColumn('members', 'interests_multi_select')) {
                $table->json('interests_multi_select')->nullable()->after('hobbies');
            }
            if (! Schema::hasColumn('members', 'health_conditions')) {
                $table->text('health_conditions')->nullable()->after('interests_multi_select');
            }
            if (! Schema::hasColumn('members', 'languages_spoken_fluently')) {
                $table->json('languages_spoken_fluently')->nullable()->after('health_conditions');
            }
            if (! Schema::hasColumn('members', 'favorite_weekend_activities')) {
                $table->string('favorite_weekend_activities', 255)->nullable()->after('languages_spoken_fluently');
            }
            if (! Schema::hasColumn('members', 'proposal_preferences')) {
                $table->string('proposal_preferences', 100)->nullable()->after('favorite_weekend_activities');
            }
            if (! Schema::hasColumn('members', 'communication_preferences')) {
                $table->json('communication_preferences')->nullable()->after('proposal_preferences');
            }

            // Step 9 - Media
            if (! Schema::hasColumn('members', 'cover_photo')) {
                $table->string('cover_photo', 255)->nullable()->after('proposal_preferences');
            }
            if (! Schema::hasColumn('members', 'private_gallery')) {
                $table->json('private_gallery')->nullable()->after('cover_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $columns = [
                'disability', 'looking_for', 'life_values', 'personality_type',
                'communication_style', 'love_language', 'conflict_resolution_style',
                'religious_practice_level', 'prayer_frequency', 'community_biradari',
                'hijab_beard_preference', 'education_level', 'employment_status',
                'work_location_city', 'annual_income', 'father_occupation',
                'mother_occupation', 'family_type', 'siblings_brothers',
                'siblings_sisters', 'married_siblings', 'family_location',
                'guardian_name', 'guardian_contact', 'family_values', 'family_bio',
                'family_expectations', 'parents_contact', 'children_preference',
                'relocation_preference', 'visa_immigration_status',
                'future_living_preference', 'financial_responsibility',
                'marriage_timeline', 'expectations_after_marriage',
                'willing_to_work_after_marriage', 'expects_spouse_to_work',
                'hobbies', 'interests_multi_select', 'health_conditions',
                'languages_spoken_fluently', 'favorite_weekend_activities',
                'proposal_preferences', 'communication_preferences',
                'cover_photo', 'private_gallery',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
