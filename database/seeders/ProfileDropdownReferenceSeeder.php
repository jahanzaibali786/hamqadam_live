<?php

namespace Database\Seeders;

use App\Support\ProfileDropdownReferenceData;
use Illuminate\Database\Seeder;

class ProfileDropdownReferenceSeeder extends Seeder
{
    public function run(): void
    {
        ProfileDropdownReferenceData::seed();
    }
}
