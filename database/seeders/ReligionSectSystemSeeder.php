<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReligionSectSystemSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('religions') || !Schema::hasTable('sect_main') || !Schema::hasTable('school_of_thought') || !Schema::hasTable('traditions')) {
            return;
        }

        $now = now();

        // Get Islam religion ID
        $islamId = DB::table('religions')->where('name', 'Islam')->value('id');
        if (!$islamId) {
            return;
        }

        // Main Sects for Islam
        $mainSects = [
            'Sunni',
            'Shia',
            'Ibadi',
            'Ahmadiyya',
            'Other',
            'Prefer not to say',
        ];

        foreach ($mainSects as $index => $sect) {
            $slug = str()->slug($sect);
            DB::table('sect_main')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $sect,
                    'religion_id' => $islamId,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Schools of Thought by Main Sect
        $schoolsOfThoughtBySect = [
            'Sunni' => [
                'Hanafi',
                'Shafi\'i',
                'Maliki',
                'Hanbali',
                'Ahl-e-Hadith / Salafi',
                'Other',
                'Prefer not to say',
            ],
            'Shia' => [
                'Twelver / Ithna Ashari / Ja\'fari',
                'Ismaili',
                'Zaidi',
                'Other',
                'Prefer not to say',
            ],
            'Ibadi' => [
                'Ibadi',
                'Prefer not to say',
            ],
            'Ahmadiyya' => [
                'Ahmadiyya Muslim Community',
                'Lahore Ahmadiyya Movement',
                'Prefer not to say',
            ],
            'Other' => [
                'Other',
                'Prefer not to say',
            ],
        ];

        foreach ($schoolsOfThoughtBySect as $sectName => $schools) {
            $sectRecord = DB::table('sect_main')->where('name', $sectName)->where('religion_id', $islamId)->first();
            if (!$sectRecord) continue;

            foreach ($schools as $index => $school) {
                $slug = str()->slug($school);
                // Make slugs unique by adding sect prefix if needed
                if ($school === 'Other' || $school === 'Prefer not to say') {
                    $slug = str()->slug($sectName . '-' . $school);
                }
                DB::table('school_of_thought')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $school,
                        'sect_main_id' => $sectRecord->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // Sunni Traditions (linked to Hanafi school of thought primarily)
        $hanafiSchool = DB::table('school_of_thought')
            ->join('sect_main', 'school_of_thought.sect_main_id', '=', 'sect_main.id')
            ->where('school_of_thought.name', 'Hanafi')
            ->where('sect_main.name', 'Sunni')
            ->where('sect_main.religion_id', $islamId)
            ->select('school_of_thought.id')
            ->first();

        if ($hanafiSchool) {
            $sunniTraditions = [
                'Barelvi',
                'Deobandi',
                'Ahl-e-Hadith',
                'Other',
                'Prefer not to say',
            ];

            foreach ($sunniTraditions as $index => $tradition) {
                $slug = str()->slug($tradition);
                // Make slugs unique by adding school prefix if needed
                if ($tradition === 'Other' || $tradition === 'Prefer not to say') {
                    $slug = str()->slug('hanafi-' . $tradition);
                }
                DB::table('traditions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $tradition,
                        'school_of_thought_id' => $hanafiSchool->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}