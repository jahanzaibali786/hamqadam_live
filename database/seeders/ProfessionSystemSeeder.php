<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfessionSystemSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('profession_categories') || !Schema::hasTable('professions')) {
            return;
        }

        $now = now();

        // Profession Categories
        $categories = [
            'Medical & Healthcare',
            'Engineering',
            'Information Technology & AI',
            'Business, Finance & Management',
            'Education & Academia',
            'Law & Legal',
            'Government, Public Service & Administration',
            'Media, Arts & Creative',
            'Skilled Trades & Technical',
            'Retail, Hospitality & Services',
            'Agriculture & Livestock',
            'Transport & Logistics',
            'Skilled Professional / Other',
        ];

        foreach ($categories as $index => $category) {
            DB::table('profession_categories')->updateOrInsert(
                ['name' => $category],
                [
                    'slug' => str()->slug($category),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Professions by Category
        $professionsByCategory = [
            'Medical & Healthcare' => [
                'Doctor / Physician', 'Dentist', 'Pharmacist', 'Nurse', 'Midwife',
                'Physiotherapist', 'Occupational Therapist', 'Psychologist', 'Nutritionist / Dietitian',
                'Medical Lab Technologist', 'Radiologist / Radiology Technologist', 'Optometrist',
                'Veterinarian', 'Paramedic / EMT', 'Public Health Professional', 'Healthcare Administrator',
                'Medical Researcher', 'Other Healthcare Professional',
            ],
            'Engineering' => [
                'Civil Engineer', 'Mechanical Engineer', 'Electrical Engineer', 'Electronics Engineer',
                'Chemical Engineer', 'Computer Engineer', 'Software Engineer', 'Telecommunication Engineer',
                'Environmental Engineer', 'Biomedical Engineer', 'Industrial Engineer', 'Aerospace Engineer',
                'Petroleum Engineer', 'Agricultural Engineer', 'Mining Engineer', 'Mechatronics Engineer',
                'Architect', 'Other Engineering Professional',
            ],
            'Information Technology & AI' => [
                'Software Developer', 'Web Developer', 'Mobile App Developer', 'Full-Stack Developer',
                'Data Scientist', 'Data Analyst', 'AI / Machine Learning Engineer', 'AI Researcher',
                'Automation Engineer', 'Cloud Engineer', 'DevOps Engineer', 'Cybersecurity Specialist',
                'Database Administrator', 'Network Engineer', 'UI/UX Designer', 'Product Manager',
                'IT Project Manager', 'IT Support Specialist', 'System Administrator', 'Game Developer',
                'Blockchain Developer', 'Other IT Professional',
            ],
            'Business, Finance & Management' => [
                'Business Owner / Entrepreneur', 'CEO / Managing Director', 'Manager', 'Operations Manager',
                'Project Manager', 'Business Analyst', 'Accountant', 'Chartered Accountant (CA)',
                'Cost & Management Accountant (CMA)', 'Financial Analyst', 'Banker', 'Investment Professional',
                'Auditor', 'Tax Consultant', 'Insurance Professional', 'HR Manager / HR Professional',
                'Procurement Specialist', 'Supply Chain Professional', 'Sales Professional', 'Marketing Manager',
                'Business Development Professional', 'Consultant', 'Economist', 'Other Business Professional',
            ],
            'Education & Academia' => [
                'School Teacher', 'College Lecturer', 'University Lecturer / Assistant Professor',
                'Professor', 'Researcher', 'Principal / Head of Institution', 'Education Administrator',
                'Special Education Teacher', 'Tutor / Trainer', 'Academic Counselor', 'Other Education Professional',
            ],
            'Law & Legal' => [
                'Lawyer / Advocate', 'Legal Advisor', 'Corporate Lawyer', 'Judge / Judicial Officer',
                'Prosecutor', 'Legal Consultant', 'Paralegal / Legal Assistant', 'Other Legal Professional',
            ],
            'Government, Public Service & Administration' => [
                'Civil Servant', 'Government Officer', 'Administrative Officer', 'Police Officer',
                'Armed Forces Officer', 'Government Teacher', 'Revenue Officer', 'Foreign Service / Diplomat',
                'Public Administrator', 'Other Government Professional',
            ],
            'Media, Arts & Creative' => [
                'Journalist', 'Writer / Author', 'Content Creator', 'YouTuber', 'Social Media Manager',
                'Graphic Designer', 'Photographer', 'Videographer', 'Video Editor', 'Animator',
                'Illustrator', 'Artist', 'Musician', 'Singer', 'Actor / Actress', 'Film / TV Professional',
                'Fashion Designer', 'Interior Designer', 'Other Creative Professional',
            ],
            'Skilled Trades & Technical' => [
                'Electrician', 'Plumber', 'Carpenter', 'Welder', 'Mechanic', 'Technician',
                'HVAC Technician', 'Auto Technician', 'Mobile Repair Technician', 'Computer Hardware Technician',
                'Tailor / Dressmaker', 'Beautician / Makeup Artist', 'Chef / Cook', 'Baker', 'Other Skilled Professional',
            ],
            'Retail, Hospitality & Services' => [
                'Shop Owner', 'Retail Manager', 'Salesperson', 'Cashier', 'Hotel Manager',
                'Restaurant Manager', 'Chef', 'Waiter / Server', 'Travel Agent', 'Tour Guide',
                'Customer Service Representative', 'Call Center Professional', 'Real Estate Agent',
                'Property Manager', 'Other Service Professional',
            ],
            'Agriculture & Livestock' => [
                'Farmer', 'Agricultural Officer', 'Agronomist', 'Horticulturist', 'Livestock Farmer',
                'Veterinary Professional', 'Fisheries Professional', 'Agribusiness Professional', 'Other Agriculture Professional',
            ],
            'Transport & Logistics' => [
                'Pilot', 'Cabin Crew', 'Driver', 'Truck / Bus Driver', 'Railway Professional',
                'Logistics Manager', 'Warehouse Manager', 'Supply Chain Worker', 'Delivery Rider / Courier',
                'Shipping / Maritime Professional', 'Other Transport Professional',
            ],
            'Skilled Professional / Other' => [
                'Freelancer', 'Digital Marketer', 'SEO Specialist', 'E-commerce Professional',
                'Affiliate Marketer', 'Online Business Owner', 'Homemaker', 'Student', 'Retired',
                'Unemployed / Looking for Work', 'Other', 'Prefer not to say',
            ],
        ];

        foreach ($professionsByCategory as $category => $professions) {
            $categoryRecord = DB::table('profession_categories')->where('name', $category)->first();
            if (!$categoryRecord) continue;

            foreach ($professions as $index => $profession) {
                DB::table('professions')->updateOrInsert(
                    [
                        'name' => $profession,
                        'profession_category_id' => $categoryRecord->id,
                    ],
                    [
                        'slug' => str()->slug($profession),
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