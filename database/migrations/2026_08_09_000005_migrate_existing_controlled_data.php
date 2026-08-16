<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // This migration handles data migration from existing free-text fields to new controlled dropdown IDs
        // It preserves backward compatibility by keeping legacy fields with _legacy suffix
        
        // Migrate member profession data
        $this->migrateMemberProfessions();
        
        // Migrate member education data
        $this->migrateMemberEducation();
        
        // Migrate member religion/sect data
        $this->migrateMemberReligionSect();
        
        // Migrate career profession data
        $this->migrateCareerProfessions();
        
        // Migrate education institution data
        $this->migrateEducationInstitutions();
    }

    private function migrateMemberProfessions(): void
    {
        if (!Schema::hasTable('members') || !Schema::hasTable('professions')) {
            return;
        }

        // Check if profession column exists
        if (!Schema::hasColumn('members', 'profession')) {
            return;
        }

        // Get all members with legacy profession values
        $members = DB::table('members')
            ->whereNotNull('profession')
            ->whereNull('profession_id')
            ->get();

        foreach ($members as $member) {
            // Try to find matching profession by name
            $profession = DB::table('professions')
                ->where('name', 'like', '%' . $member->profession . '%')
                ->where('is_active', true)
                ->first();

            if ($profession) {
                DB::table('members')
                    ->where('id', $member->id)
                    ->update([
                        'profession_id' => $profession->id,
                        'profession_category_id' => $profession->profession_category_id,
                    ]);
            }
        }
    }

    private function migrateMemberEducation(): void
    {
        if (!Schema::hasTable('members') || !Schema::hasTable('education_levels') || !Schema::hasTable('degrees')) {
            return;
        }

        // Check if education_level column exists
        if (!Schema::hasColumn('members', 'education_level')) {
            return;
        }

        // Get all members with legacy education_level values
        $members = DB::table('members')
            ->whereNotNull('education_level')
            ->whereNull('education_level_id')
            ->get();

        foreach ($members as $member) {
            // Try to find matching education level by name
            $educationLevel = DB::table('education_levels')
                ->where('name', 'like', '%' . $member->education_level . '%')
                ->where('is_active', true)
                ->first();

            if ($educationLevel) {
                DB::table('members')
                    ->where('id', $member->id)
                    ->update(['education_level_id' => $educationLevel->id]);
            }
        }
    }

    private function migrateMemberReligionSect(): void
    {
        if (!Schema::hasTable('members') || !Schema::hasTable('sect_main') || !Schema::hasTable('religions')) {
            return;
        }

        // Get all members with Islam religion but no sect data
        $members = DB::table('members')
            ->join('spiritual_backgrounds', 'members.user_id', '=', 'spiritual_backgrounds.user_id')
            ->where('spiritual_backgrounds.religion_id', function($query) {
                $query->select('id')->from('religions')->where('name', 'Islam');
            })
            ->whereNull('members.sect_main_id')
            ->get(['members.id', 'members.user_id']);

        foreach ($members as $member) {
            // Set default to Sunni if no sect specified
            $sunniSect = DB::table('sect_main')
                ->where('name', 'Sunni')
                ->where('religion_id', function($query) {
                    $query->select('id')->from('religions')->where('name', 'Islam');
                })
                ->first();

            if ($sunniSect) {
                DB::table('members')
                    ->where('id', $member->id)
                    ->update(['sect_main_id' => $sunniSect->id]);
                
                DB::table('spiritual_backgrounds')
                    ->where('user_id', $member->user_id)
                    ->update(['sect_main_id' => $sunniSect->id]);
            }
        }
    }

    private function migrateCareerProfessions(): void
    {
        if (!Schema::hasTable('careers') || !Schema::hasTable('professions')) {
            return;
        }

        // Get all careers with legacy designation values
        $careers = DB::table('careers')
            ->whereNotNull('designation')
            ->whereNull('profession_id')
            ->get();

        foreach ($careers as $career) {
            // Try to find matching profession by name
            $profession = DB::table('professions')
                ->where('name', 'like', '%' . $career->designation . '%')
                ->where('is_active', true)
                ->first();

            if ($profession) {
                DB::table('careers')
                    ->where('id', $career->id)
                    ->update([
                        'profession_id' => $profession->id,
                        'profession_category_id' => $profession->profession_category_id,
                    ]);
            }
        }
    }

    private function migrateEducationInstitutions(): void
    {
        if (!Schema::hasTable('education') || !Schema::hasTable('institutions')) {
            return;
        }

        // Check if columns exist
        $hasLegacy = Schema::hasColumn('education', 'institution_legacy');
        $hasOriginal = Schema::hasColumn('education', 'institution');
        
        if (!$hasLegacy && !$hasOriginal) {
            return;
        }

        // Get all education records with legacy institution values
        $query = DB::table('education')->whereNull('institution_id');
        
        if ($hasLegacy) {
            $query->orWhereNotNull('institution_legacy');
        }
        if ($hasOriginal) {
            $query->orWhereNotNull('institution');
        }
        
        $educations = $query->get();

        foreach ($educations as $education) {
            $institutionName = $education->institution_legacy ?? ($education->institution ?? null);
            
            if (!$institutionName) continue;
            
            // Try to find matching institution by name
            $institution = DB::table('institutions')
                ->where('name', 'like', '%' . $institutionName . '%')
                ->where('is_active', true)
                ->first();

            if ($institution) {
                DB::table('education')
                    ->where('id', $education->id)
                    ->update(['institution_id' => $institution->id]);
            }
        }
    }

    public function down(): void
    {
        // Rollback - remove new field mappings but keep legacy data
        DB::table('members')->update([
            'profession_id' => null,
            'profession_category_id' => null,
            'education_level_id' => null,
            'degree_id' => null,
            'field_of_study_id' => null,
            'institution_id' => null,
            'sect_main_id' => null,
            'school_of_thought_id' => null,
            'tradition_id' => null,
        ]);

        DB::table('careers')->update([
            'profession_id' => null,
            'profession_category_id' => null,
        ]);

        DB::table('education')->update([
            'education_level_id' => null,
            'degree_id' => null,
            'field_of_study_id' => null,
            'institution_id' => null,
        ]);

        DB::table('spiritual_backgrounds')->update([
            'sect_main_id' => null,
            'school_of_thought_id' => null,
            'tradition_id' => null,
        ]);
    }
};