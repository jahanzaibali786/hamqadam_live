<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProfileDropdownReferenceSeeder::class);
        $this->call(ProfessionSystemSeeder::class);
        $this->call(EducationSystemSeeder::class);
        $this->call(ReligionSectSystemSeeder::class);
        $this->call(InstitutionsSystemSeeder::class);
        $this->call(DemoMatrimonialSeeder::class);
    }
}
