<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstitutionsSystemSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('institutions') || !Schema::hasTable('countries') || !Schema::hasTable('states') || !Schema::hasTable('cities')) {
            return;
        }

        $now = now();

        // Get Pakistan country ID
        $pakistanId = DB::table('countries')->where('name', 'Pakistan')->value('id');
        if (!$pakistanId) {
            return;
        }

        // Institutions by Province/Region and City
        $institutionsByLocation = [
            'Punjab' => [
                'Lahore' => [
                    'University of the Punjab (PU)',
                    'University of Engineering and Technology (UET) Lahore',
                    'Government College University (GCU) Lahore',
                    'University of Health Sciences (UHS) Lahore',
                    'King Edward Medical University (KEMU)',
                    'Lahore University of Management Sciences (LUMS)',
                    'University of Central Punjab (UCP)',
                    'University of Lahore (UOL)',
                    'University of Management and Technology (UMT)',
                    'COMSATS University Islamabad — Lahore Campus',
                    'National University of Computer and Emerging Sciences (FAST-NUCES) — Lahore Campus',
                    'Forman Christian College (A Chartered University)',
                    'Kinnaird College for Women',
                    'Government College Women University Lahore',
                    'Information Technology University (ITU)',
                    'University of Education',
                    'Fatima Jinnah Medical University',
                    'University of Veterinary and Animal Sciences (UVAS)',
                ],
                'Faisalabad' => [
                    'University of Agriculture Faisalabad (UAF)',
                    'Government College University Faisalabad (GCUF)',
                ],
                'Taxila' => [
                    'University of Engineering and Technology — Taxila',
                ],
                'Sargodha' => [
                    'University of Sargodha',
                ],
                'Gujrat' => [
                    'University of Gujrat',
                ],
                'Gujranwala' => [
                    'University of Gujranwala',
                ],
                'Sialkot' => [
                    'Government College Women University Sialkot',
                    'University of Sialkot',
                ],
                'Multan' => [
                    'Bahauddin Zakariya University (BZU)',
                    'Nishtar Medical University',
                ],
                'Rahim Yar Khan' => [
                    'Khawaja Fareed University of Engineering and Information Technology (KFUEIT)',
                ],
                'Okara' => [
                    'University of Okara',
                ],
                'Jhang' => [
                    'University of Jhang',
                ],
                'Narowal' => [
                    'University of Narowal',
                ],
                'Chakwal' => [
                    'University of Chakwal',
                ],
            ],
            'Khyber Pakhtunkhwa (KPK)' => [
                'Peshawar' => [
                    'University of Peshawar',
                    'Khyber Medical University (KMU)',
                    'University of Engineering and Technology (UET) Peshawar',
                    'Islamia College Peshawar',
                    'Institute of Management Sciences (IMSciences)',
                    'Shaheed Benazir Bhutto Women University',
                ],
                'Mardan' => [
                    'Abdul Wali Khan University Mardan',
                ],
                'Mingora / Swat' => [
                    'University of Swat',
                ],
                'Malakand' => [
                    'University of Malakand',
                ],
                'Haripur' => [
                    'University of Haripur',
                ],
                'Mansehra' => [
                    'Hazara University',
                ],
                'Abbottabad' => [
                    'COMSATS University Islamabad — Abbottabad Campus',
                ],
                'Dera Ismail Khan' => [
                    'Gomal University',
                ],
                'Bannu' => [
                    'University of Science and Technology Bannu',
                ],
                'Kohat' => [
                    'Kohat University of Science and Technology (KUST)',
                ],
                'Chitral' => [
                    'University of Chitral',
                ],
            ],
            'Sindh' => [
                'Karachi' => [
                    'University of Karachi',
                    'NED University of Engineering and Technology',
                    'Dow University of Health Sciences',
                    'Institute of Business Administration (IBA)',
                    'Aga Khan University',
                    'Habib University',
                    'SZABIST',
                    'Bahria University — Karachi Campus',
                    'Sir Syed University of Engineering and Technology',
                    'DHA Suffa University',
                    'Indus University',
                    'Iqra University',
                    'Jinnah Sindh Medical University',
                ],
                'Jamshoro' => [
                    'University of Sindh',
                    'Mehran University of Engineering and Technology (MUET)',
                    'Liaquat University of Medical & Health Sciences (LUMHS)',
                ],
                'Sukkur' => [
                    'Sukkur IBA University',
                ],
                'Khairpur' => [
                    'Shah Abdul Latif University',
                ],
                'Shaheed Benazirabad' => [
                    'Shaheed Benazir Bhutto University',
                ],
                'Nawabshah' => [
                    'People\'s University of Medical & Health Sciences',
                ],
                'Larkana' => [
                    'University of Larkana',
                ],
                'Hyderabad' => [
                    'Isra University',
                ],
            ],
            'Balochistan' => [
                'Quetta' => [
                    'University of Balochistan',
                    'Balochistan University of Information Technology, Engineering and Management Sciences (BUITEMS)',
                    'Bolan University of Medical and Health Sciences',
                    'Islamia College Quetta',
                    'Sardar Bahadur Khan Women\'s University',
                ],
                'Khuzdar' => [
                    'University of Engineering and Technology Balochistan',
                ],
                'Turbat' => [
                    'University of Turbat',
                ],
                'Uthal' => [
                    'Lasbela University of Agriculture, Water and Marine Sciences (LUAWMS)',
                ],
                'Gwadar' => [
                    'University of Gwadar',
                ],
            ],
            'Islamabad Capital Territory' => [
                'Islamabad' => [
                    'Quaid-i-Azam University',
                    'COMSATS University Islamabad',
                    'National University of Sciences and Technology (NUST)',
                    'International Islamic University Islamabad (IIUI)',
                    'Air University',
                    'Bahria University',
                    'National University of Modern Languages (NUML)',
                    'Riphah International University',
                    'Pakistan Institute of Development Economics (PIDE)',
                    'Shifa Tameer-e-Millat University',
                    'Pakistan Institute of Engineering and Applied Sciences (PIEAS)',
                    'Institute of Space Technology (IST)',
                    'National Defence University (NDU)',
                ],
            ],
            'Azad Jammu & Kashmir (AJK)' => [
                'Muzaffarabad' => [
                    'University of Azad Jammu & Kashmir',
                    'AJK University of Medical Sciences',
                ],
                'Mirpur' => [
                    'Mirpur University of Science and Technology (MUST)',
                ],
                'Rawalakot' => [
                    'University of Poonch Rawalakot',
                ],
                'Kotli' => [
                    'University of Kotli Azad Jammu & Kashmir',
                ],
            ],
            'Gilgit-Baltistan (GB)' => [
                'Skardu' => [
                    'University of Baltistan',
                ],
                'Gilgit' => [
                    'Karakoram International University',
                ],
            ],
        ];

        // Major Colleges (will be added as type 'College')
        $majorColleges = [
            'Lahore' => [
                'Government College Lahore (historical)',
                'Islamia College Civil Lines — Lahore',
                'Government College Township — Lahore',
                'Government College of Science — Lahore',
                'Government College for Women — Lahore',
            ],
            'Peshawar' => [
                'Islamia College Peshawar',
                'Edwardes College',
                'Government College Peshawar',
            ],
            'Rawalpindi' => [
                'Government College Rawalpindi',
            ],
            'Faisalabad' => [
                'Government College Faisalabad',
            ],
            'Hyderabad' => [
                'Government College Hyderabad',
            ],
            'Karachi' => [
                'DJ Sindh Government Science College',
                'Government National College',
                'Islamia College Civil Lines — Karachi',
            ],
        ];

        foreach ($institutionsByLocation as $province => $cities) {
            $stateRecord = DB::table('states')->where('name', $province)->where('country_id', $pakistanId)->first();
            if (!$stateRecord) continue;

            foreach ($cities as $city => $institutions) {
                $cityRecord = DB::table('cities')->where('name', $city)->where('state_id', $stateRecord->id)->first();
                if (!$cityRecord) continue;

                foreach ($institutions as $index => $institution) {
                    DB::table('institutions')->updateOrInsert(
                        [
                            'name' => $institution,
                            'city_id' => $cityRecord->id,
                        ],
                        [
                            'country_id' => $pakistanId,
                            'state_id' => $stateRecord->id,
                            'slug' => str()->slug($institution),
                            'type' => 'University',
                            'sort_order' => $index + 1,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }

        // Add major colleges
        foreach ($majorColleges as $city => $colleges) {
            $cityRecord = DB::table('cities')->where('name', $city)->first();
            if (!$cityRecord) continue;

            $stateRecord = DB::table('states')->where('id', $cityRecord->state_id)->first();
            if (!$stateRecord) continue;

            foreach ($colleges as $index => $college) {
                DB::table('institutions')->updateOrInsert(
                    [
                        'name' => $college,
                        'city_id' => $cityRecord->id,
                    ],
                    [
                        'country_id' => $pakistanId,
                        'state_id' => $stateRecord->id,
                        'slug' => str()->slug($college),
                        'type' => 'College',
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