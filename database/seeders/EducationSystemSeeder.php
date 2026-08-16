<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EducationSystemSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('education_levels') || !Schema::hasTable('degrees')) {
            return;
        }

        $now = now();

        // Education Levels
        $educationLevels = [
            'Matric / SSC',
            'Intermediate / HSSC',
            'Associate Degree / ADP',
            'Diploma / DAE / Technical Diploma',
            'Certificate',
            "Bachelor's Degree",
            "Master's Degree",
            'MS / MPhil',
            'PhD',
            'Postdoctoral',
            'Other',
            'Prefer not to say',
        ];

        foreach ($educationLevels as $index => $level) {
            DB::table('education_levels')->updateOrInsert(
                ['name' => $level],
                [
                    'slug' => str()->slug($level),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Degrees by Category
        $degreesByCategory = [
            'Computer Science, IT & AI' => [
                'BS Computer Science (BSCS)', 'BS Software Engineering (BSSE)', 'BS Information Technology (BSIT)',
                'BS Artificial Intelligence (BSAI)', 'BS Data Science', 'BS Cyber Security', 'BS Information Systems',
                'BS Computer Engineering', 'BS Bioinformatics', 'BS Game Development', 'BS Cloud Computing',
                'BS Robotics / Intelligent Systems', 'BS Mathematics & Computer Science', 'MS Computer Science',
                'MS Software Engineering', 'MS Artificial Intelligence', 'MS Data Science', 'MS Cyber Security',
                'MS Information Technology', 'MPhil Computer Science', 'PhD Computer Science', 'PhD Artificial Intelligence',
            ],
            'Engineering' => [
                'BE / BS Civil Engineering', 'BE / BS Mechanical Engineering', 'BE / BS Electrical Engineering',
                'BE / BS Electronics Engineering', 'BE / BS Computer Engineering', 'BE / BS Mechatronics Engineering',
                'BE / BS Chemical Engineering', 'BE / BS Environmental Engineering', 'BE / BS Industrial Engineering',
                'BE / BS Biomedical Engineering', 'BE / BS Telecommunication Engineering', 'BE / BS Software Engineering',
                'BE / BS Aerospace Engineering', 'BE / BS Petroleum Engineering', 'BE / BS Mining Engineering',
                'BE / BS Agricultural Engineering', 'BE / BS Textile Engineering', 'BE / BS Metallurgical / Materials Engineering',
                'MS / ME Engineering', 'MPhil Engineering', 'PhD Engineering',
            ],
            'Medical & Health Sciences' => [
                'MBBS', 'BDS', 'Pharm-D', 'DPT / Doctor of Physical Therapy', 'BS Nursing', 'Post-RN BSN',
                'BS Medical Laboratory Technology', 'BS Medical Imaging Technology / Radiology', 'BS Optometry',
                'BS Nutrition & Dietetics', 'BS Public Health', 'BS Biotechnology', 'BS Microbiology',
                'BS Medical Sciences', 'BS Occupational Therapy', 'BS Speech & Language Pathology',
                'Doctor of Pharmacy', 'MD', 'MS / MPhil Health Sciences', 'MPH', 'FCPS', 'MRCP', 'PhD Health Sciences',
            ],
            'Business, Commerce, Finance & Economics' => [
                'BBA', 'BBIT / Business & IT', 'BS Accounting & Finance', 'BS Accounting', 'BS Finance',
                'BS Economics', 'BS Business Analytics', 'BS Entrepreneurship', 'BS Marketing',
                'BS Human Resource Management', 'BS Supply Chain Management', 'BS Management', 'BS Commerce',
                'B.Com', 'MBA', 'M.Com', 'MS Finance', 'MS Management', 'MS Marketing', 'MS Business Analytics',
                'MS Economics', 'MPhil Economics', 'PhD Management', 'PhD Economics', 'CA', 'ACCA', 'CMA', 'CFA',
            ],
            'Social Sciences' => [
                'BS Psychology', 'BS Sociology', 'BS Political Science', 'BS International Relations',
                'BS Public Administration', 'BS Anthropology', 'BS Social Work', 'BS Development Studies',
                'BS Gender Studies', 'BS Criminology', 'BS Peace & Conflict Studies', 'BS Defence & Strategic Studies',
                'BS Population Studies', 'BS Public Policy', 'MS / MPhil Psychology', 'MS / MPhil Sociology',
                'MS / MPhil Political Science', 'MS / MPhil International Relations', 'PhD Social Sciences',
            ],
            'Law' => [
                'LLB', 'LLM', 'JD', 'PhD Law', 'Bar / Professional Legal Qualification',
            ],
            'Education' => [
                'Associate Degree in Education (ADE)', 'B.Ed', 'B.Ed (Hons)', 'BS Education',
                'BS Early Childhood Education', 'BS Special Education', 'BS Educational Leadership & Management',
                'M.Ed', 'MA Education', 'MS / MPhil Education', 'PhD Education',
            ],
            'Arts, Humanities & Languages' => [
                'BA', 'BS English', 'BS Urdu', 'BS Arabic', 'BS Islamic Studies', 'BS History', 'BS Philosophy',
                'BS Fine Arts', 'BS Visual Arts', 'BS Islamic Art / History', 'BS Linguistics', 'BS Literature',
                'MA English', 'MA Urdu', 'MA Islamic Studies', 'MA History', 'MS / MPhil English',
                'MS / MPhil Urdu', 'MS / MPhil Islamic Studies', 'PhD Humanities',
            ],
            'Media, Communication & Creative Arts' => [
                'BS Mass Communication', 'BS Media Studies', 'BS Journalism', 'BS Film & TV', 'BS Digital Media',
                'BS Advertising & Public Relations', 'BS Graphic Design', 'BS Multimedia Design', 'BS Animation',
                'BS Fashion Design', 'BS Interior Design', 'BS Architecture', 'MA / MS Media Studies',
                'MS Film & TV', 'MS Communication', 'MFA', 'M.Arch',
            ],
            'Natural Sciences' => [
                'BS Physics', 'BS Chemistry', 'BS Mathematics', 'BS Statistics', 'BS Biology', 'BS Zoology',
                'BS Botany', 'BS Environmental Science', 'BS Geology', 'BS Geography', 'BS Biochemistry',
                'BS Biotechnology', 'MS Physics', 'MS Chemistry', 'MS Mathematics', 'MS Statistics',
                'MS Biology', 'MPhil Natural Sciences', 'PhD Natural Sciences',
            ],
            'Agriculture, Veterinary & Food Sciences' => [
                'BS Agriculture', 'BS Agronomy', 'BS Horticulture', 'BS Plant Breeding & Genetics',
                'BS Soil Science', 'BS Animal Sciences', 'DVM / Doctor of Veterinary Medicine', 'BS Dairy Science',
                'BS Fisheries', 'BS Food Science & Technology', 'BS Food & Nutrition', 'BS Forestry',
                'MS Agriculture', 'MS Food Science', 'MS Veterinary Sciences', 'PhD Agriculture / Veterinary Sciences',
            ],
            'Architecture, Planning & Built Environment' => [
                'B.Arch', 'BS Architecture', 'BS City & Regional Planning', 'BS Urban Planning',
                'BS Landscape Architecture', 'BS Construction Management', 'BS Interior Design',
                'M.Arch', 'MS Urban Planning', 'MS Construction Management',
            ],
            'Islamic & Religious Studies' => [
                'BS Islamic Studies', 'BS Arabic', 'Shahadat-ul-Alamia / Alimiyyah', 'MA Islamic Studies',
                'MA Arabic', 'MS / MPhil Islamic Studies', 'MS / MPhil Arabic', 'PhD Islamic Studies',
            ],
            'Aviation, Maritime & Transport' => [
                'BS Aviation Management', 'BS Aviation Technology', 'Commercial Pilot Licence (CPL)',
                'BS Maritime Studies', 'BS Nautical Science', 'BS Logistics & Supply Chain',
                'MS Aviation Management', 'Other Aviation / Maritime Qualification',
            ],
            'Hospitality, Tourism & Culinary' => [
                'BS Hospitality Management', 'BS Tourism Management', 'BS Travel & Tourism',
                'BS Hotel Management', 'BS Culinary Arts', 'BS Event Management', 'MS Hospitality / Tourism',
            ],
            'Other / Professional' => [
                'Other Bachelor\'s Degree', 'Other Master\'s Degree', 'Other MS / MPhil', 'Other PhD',
                'Professional Qualification', 'Technical Diploma', 'Vocational Diploma', 'Certificate / Short Course',
                'Other', 'Prefer not to say',
            ],
        ];

        foreach ($degreesByCategory as $category => $degrees) {
            foreach ($degrees as $index => $degree) {
                // Map degree to education level
                $educationLevelId = $this->getEducationLevelId($degree);
                $slug = str()->slug($degree);
                
                DB::table('degrees')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $degree,
                        'category' => $category,
                        'education_level_id' => $educationLevelId,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // Seed fields of study
        $this->seedFieldsOfStudy();
    }

    private function getEducationLevelId($degree): ?int
    {
        $now = now();
        
        // Simple mapping based on degree name patterns
        if (str_contains($degree, 'PhD') || str_contains($degree, 'Doctor')) {
            return DB::table('education_levels')->where('name', 'PhD')->value('id');
        }
        if (str_contains($degree, 'MS') || str_contains($degree, 'MPhil') || str_contains($degree, 'M.Arch') || str_contains($degree, 'MFA')) {
            return DB::table('education_levels')->where('name', 'MS / MPhil')->value('id');
        }
        if (str_contains($degree, 'Master') || str_contains($degree, 'MBA') || str_contains($degree, 'MA') || str_contains($degree, 'M.Ed') || str_contains($degree, 'M.Com') || str_contains($degree, 'MPH') || str_contains($degree, 'MRCP') || str_contains($degree, 'FCPS')) {
            return DB::table('education_levels')->where('name', "Master's Degree")->value('id');
        }
        if (str_contains($degree, 'BS') || str_contains($degree, 'BE') || str_contains($degree, 'B.Arch') || str_contains($degree, 'BBA') || str_contains($degree, 'B.Com') || str_contains($degree, 'B.Ed') || str_contains($degree, 'LLB') || str_contains($degree, 'MBBS') || str_contains($degree, 'BDS') || str_contains($degree, 'DVM') || str_contains($degree, 'DPT') || str_contains($degree, 'Pharm-D') || str_contains($degree, 'JD')) {
            return DB::table('education_levels')->where('name', "Bachelor's Degree")->value('id');
        }
        if (str_contains($degree, 'Associate') || str_contains($degree, 'ADP') || str_contains($degree, 'ADE')) {
            return DB::table('education_levels')->where('name', 'Associate Degree / ADP')->value('id');
        }
        if (str_contains($degree, 'Diploma') || str_contains($degree, 'DAE')) {
            return DB::table('education_levels')->where('name', 'Diploma / DAE / Technical Diploma')->value('id');
        }
        if (str_contains($degree, 'Certificate')) {
            return DB::table('education_levels')->where('name', 'Certificate')->value('id');
        }
        
        // Default to Bachelor's for other degrees
        return DB::table('education_levels')->where('name', "Bachelor's Degree")->value('id');
    }

    private function seedFieldsOfStudy(): void
    {
        if (!Schema::hasTable('fields_of_study')) {
            return;
        }

        $now = now();

        // Fields of study by degree category
        $fieldsOfStudyByCategory = [
            'Computer Science, IT & AI' => [
                'Artificial Intelligence', 'Machine Learning', 'Data Science', 'Cyber Security',
                'Software Engineering', 'Web Development', 'Mobile App Development', 'Cloud Computing',
                'Computer Networks', 'Database Systems', 'Algorithms', 'Computer Graphics',
                'Bioinformatics', 'Robotics', 'Game Development', 'Information Systems',
            ],
            'Engineering' => [
                'Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering',
                'Electronics Engineering', 'Computer Engineering', 'Mechatronics Engineering',
                'Chemical Engineering', 'Environmental Engineering', 'Industrial Engineering',
                'Biomedical Engineering', 'Telecommunication Engineering', 'Aerospace Engineering',
                'Petroleum Engineering', 'Mining Engineering', 'Agricultural Engineering',
                'Textile Engineering', 'Materials Engineering',
            ],
            'Medical & Health Sciences' => [
                'Medicine', 'Surgery', 'Pediatrics', 'Obstetrics & Gynecology', 'Cardiology',
                'Neurology', 'Orthopedics', 'Ophthalmology', 'Dentistry', 'Pharmacy',
                'Physical Therapy', 'Nursing', 'Medical Laboratory Science', 'Radiology',
                'Optometry', 'Nutrition & Dietetics', 'Public Health', 'Biotechnology',
                'Microbiology', 'Medical Sciences', 'Occupational Therapy', 'Speech & Language Pathology',
            ],
            'Business, Commerce, Finance & Economics' => [
                'Accounting', 'Finance', 'Marketing', 'Human Resource Management',
                'Supply Chain Management', 'Management', 'Entrepreneurship', 'Business Analytics',
                'Economics', 'International Business', 'Project Management', 'Operations Management',
                'Strategic Management', 'Financial Management', 'Investment Banking', 'Corporate Finance',
            ],
            'Social Sciences' => [
                'Psychology', 'Sociology', 'Political Science', 'International Relations',
                'Public Administration', 'Anthropology', 'Social Work', 'Development Studies',
                'Gender Studies', 'Criminology', 'Peace & Conflict Studies', 'Defence & Strategic Studies',
                'Population Studies', 'Public Policy', 'Social Psychology', 'Cultural Studies',
            ],
            'Law' => [
                'Constitutional Law', 'Criminal Law', 'Civil Law', 'Corporate Law',
                'International Law', 'Commercial Law', 'Tax Law', 'Intellectual Property Law',
                'Human Rights Law', 'Environmental Law', 'Family Law', 'Labour Law',
            ],
            'Education' => [
                'Educational Psychology', 'Curriculum Development', 'Educational Leadership',
                'Early Childhood Education', 'Special Education', 'Adult Education',
                'Educational Technology', 'Higher Education', 'Vocational Education',
                'Educational Assessment', 'Teacher Education', 'Educational Policy',
            ],
            'Arts, Humanities & Languages' => [
                'English Literature', 'Urdu Literature', 'Arabic Literature', 'Islamic Studies',
                'History', 'Philosophy', 'Fine Arts', 'Visual Arts', 'Islamic Art',
                'Linguistics', 'Comparative Literature', 'Cultural Studies', 'Classical Studies',
                'Art History', 'Music', 'Theater', 'Film Studies',
            ],
            'Media, Communication & Creative Arts' => [
                'Journalism', 'Mass Communication', 'Public Relations', 'Advertising',
                'Digital Media', 'Film & Television', 'Graphic Design', 'Multimedia Design',
                'Animation', 'Fashion Design', 'Interior Design', 'Architecture',
                'Photography', 'Web Design', 'UX/UI Design', 'Game Design',
            ],
            'Natural Sciences' => [
                'Physics', 'Chemistry', 'Mathematics', 'Statistics', 'Biology',
                'Zoology', 'Botany', 'Environmental Science', 'Geology', 'Geography',
                'Biochemistry', 'Biotechnology', 'Astronomy', 'Oceanography', 'Meteorology',
                'Materials Science', 'Nanotechnology', 'Applied Mathematics', 'Theoretical Physics',
            ],
            'Agriculture, Veterinary & Food Sciences' => [
                'Agronomy', 'Horticulture', 'Plant Breeding', 'Soil Science',
                'Animal Sciences', 'Veterinary Medicine', 'Dairy Science', 'Fisheries',
                'Food Science', 'Food Technology', 'Food & Nutrition', 'Forestry',
                'Agricultural Economics', 'Agricultural Engineering', 'Pest Management',
            ],
            'Architecture, Planning & Built Environment' => [
                'Architecture', 'Urban Planning', 'City Planning', 'Regional Planning',
                'Landscape Architecture', 'Construction Management', 'Interior Design',
                'Urban Design', 'Environmental Planning', 'Transportation Planning',
                'Housing Studies', 'Real Estate Development', 'Sustainable Design',
            ],
            'Islamic & Religious Studies' => [
                'Islamic Theology', 'Islamic History', 'Islamic Philosophy', 'Arabic Language',
                'Quranic Studies', 'Hadith Studies', 'Fiqh', 'Islamic Banking',
                'Comparative Religion', 'Islamic Civilization', 'Arabic Literature',
            ],
            'Aviation, Maritime & Transport' => [
                'Aviation Management', 'Aviation Technology', 'Pilot Training',
                'Maritime Studies', 'Nautical Science', 'Logistics', 'Supply Chain',
                'Transportation Management', 'Air Traffic Control', 'Aircraft Maintenance',
            ],
            'Hospitality, Tourism & Culinary' => [
                'Hospitality Management', 'Tourism Management', 'Travel & Tourism',
                'Hotel Management', 'Culinary Arts', 'Event Management', 'Food Service',
                'Tourism Marketing', 'Resort Management', 'Restaurant Management',
            ],
        ];

        foreach ($fieldsOfStudyByCategory as $category => $fields) {
            // Get a degree from this category to link fields of study
            $degree = DB::table('degrees')->where('category', $category)->first();
            if (!$degree) continue;

            foreach ($fields as $index => $field) {
                $slug = str()->slug($field);
                DB::table('fields_of_study')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $field,
                        'degree_id' => $degree->id,
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