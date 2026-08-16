<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfileDropdownReferenceData
{
    public static function seed(): void
    {
        $now = now();

        self::seedNamedRows('on_behalves', [
            'Self',
            'Son',
            'Daughter',
            'Brother',
            'Sister',
            'Relative',
            'Friend',
            'Guardian',
        ], $now);

        self::seedNamedRows('marital_statuses', [
            'Never Married',
            'Divorced',
            'Widowed',
            'Separated',
            'Annulled',
        ], $now);

        self::seedNamedRows('family_values', [
            'Religious',
            'Moderate',
            'Traditional',
            'Liberal',
            'Family Oriented',
        ], $now);

        self::seedNamedRows('member_languages', [
            'Urdu',
            'Punjabi',
            'Pashto',
            'Sindhi',
            'Balochi',
            'Saraiki',
            'Hindko',
            'English',
            'Arabic',
        ], $now);

        self::seedNamedRows('religions', [
            'Islam',
            'Christianity',
            'Hinduism',
            'Sikhism',
            'Other',
        ], $now);

        self::seedAnnualSalaryRanges($now);
        self::seedCastes($now);
        self::seedPakistanLocations($now);
    }

    private static function seedAnnualSalaryRanges($now): void
    {
        if (! Schema::hasTable('annual_salary_ranges')) {
            return;
        }

        foreach ([
            [0, 500000],
            [500001, 1000000],
            [1000001, 2000000],
            [2000001, 3500000],
            [3500001, 5000000],
            [5000001, 10000000],
            [10000001, 20000000],
        ] as [$min, $max]) {
            self::upsert('annual_salary_ranges', ['min_salary' => $min], [
                'min_salary' => $min,
                'max_salary' => $max,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private static function seedCastes($now): void
    {
        if (! Schema::hasTable('religions') || ! Schema::hasTable('castes')) {
            return;
        }

        $islamId = DB::table('religions')->where('name', 'Islam')->value('id');
        if (! $islamId) {
            return;
        }

        $castes = [
            'Syed' => ['Bukhari', 'Gilani', 'Rizvi', 'Kazmi'],
            'Sheikh' => ['Qureshi', 'Siddiqui', 'Farooqi', 'Usmani'],
            'Rajput' => ['Bhatti', 'Rana', 'Janjua', 'Chauhan'],
            'Jutt' => ['Cheema', 'Sandhu', 'Warraich', 'Gondal'],
            'Arain' => ['Mian', 'Mehar', 'Chaudhry'],
            'Mughal' => ['Baig', 'Mirza'],
            'Pathan' => ['Yousafzai', 'Afridi', 'Khattak'],
            'Baloch' => ['Bugti', 'Marri', 'Rind'],
            'Kashmiri' => ['Butt', 'Dar', 'Lone'],
            'No Caste Preference' => ['Not Applicable'],
        ];

        foreach ($castes as $caste => $subCastes) {
            self::upsert('castes', ['name' => $caste, 'religion_id' => $islamId], [
                'name' => $caste,
                'religion_id' => $islamId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (! Schema::hasTable('sub_castes')) {
                continue;
            }

            $casteId = DB::table('castes')
                ->where('name', $caste)
                ->where('religion_id', $islamId)
                ->value('id');

            foreach ($subCastes as $subCaste) {
                self::upsert('sub_castes', ['name' => $subCaste, 'caste_id' => $casteId], [
                    'name' => $subCaste,
                    'caste_id' => $casteId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private static function seedPakistanLocations($now): void
    {
        if (! Schema::hasTable('countries')) {
            return;
        }

        self::upsert('countries', ['name' => 'Pakistan'], [
            'name' => 'Pakistan',
            'code' => 'PK',
            'phonecode' => '92',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pakistanId = DB::table('countries')->where('name', 'Pakistan')->value('id');
        if (! $pakistanId || ! Schema::hasTable('states')) {
            return;
        }

        $states = [
            'Punjab' => ['Lahore', 'Faisalabad', 'Rawalpindi', 'Gujranwala', 'Multan', 'Sialkot', 'Bahawalpur', 'Sargodha', 'Gujrat', 'Sheikhupura', 'Jhelum', 'Chakwal', 'Attock', 'Mianwali', 'Bhakkar', 'Khushab', 'Mandi Bahauddin', 'Hafizabad', 'Narowal', 'Kasur', 'Okara', 'Sahiwal', 'Pakpattan', 'Vehari', 'Khanewal', 'Lodhran', 'Muzaffargarh', 'Dera Ghazi Khan', 'Layyah', 'Rajanpur', 'Rahim Yar Khan', 'Bahawalnagar', 'Jhang', 'Toba Tek Singh', 'Chiniot', 'Nankana Sahib'],
            'Khyber Pakhtunkhwa (KPK)' => ['Peshawar', 'Mardan', 'Mingora / Swat', 'Abbottabad', 'Kohat', 'Dera Ismail Khan', 'Bannu', 'Nowshera', 'Swabi', 'Mansehra', 'Haripur', 'Charsadda', 'Takht Bhai', 'Batkhela', 'Timergara', 'Dir', 'Chitral', 'Tank', 'Lakki Marwat', 'Hangu', 'Karak', 'Shangla', 'Buner', 'Bajaur', 'Khyber', 'Mohmand', 'Kurram', 'North Waziristan', 'South Waziristan'],
            'Sindh' => ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Nawabshah / Shaheed Benazirabad', 'Mirpur Khas', 'Jacobabad', 'Shikarpur', 'Khairpur', 'Dadu', 'Thatta', 'Badin', 'Tando Adam', 'Tando Allahyar', 'Tando Muhammad Khan', 'Umerkot', 'Sanghar', 'Ghotki', 'Kashmore', 'Kotri', 'Jamshoro', 'Moro', 'Sehwan Sharif'],
            'Balochistan' => ['Quetta', 'Gwadar', 'Turbat', 'Khuzdar', 'Chaman', 'Sibi', 'Zhob', 'Loralai', 'Dera Murad Jamali', 'Dera Allah Yar', 'Hub', 'Nushki', 'Dalbandin', 'Mastung', 'Kalat', 'Kharan', 'Panjgur', 'Pasni', 'Ormara', 'Lasbela', 'Barkhan', 'Muslim Bagh'],
            'Azad Jammu & Kashmir (AJK)' => ['Muzaffarabad', 'Mirpur', 'Rawalakot', 'Kotli', 'Bagh', 'Bhimber', 'Poonch', 'Hattian Bala', 'Neelum', 'Haveli', 'Sudhanoti', 'Dhirkot', 'Palandri'],
            'Gilgit-Baltistan (GB)' => ['Gilgit', 'Skardu', 'Chilas', 'Hunza', 'Nagar', 'Ghizer', 'Astore', 'Ghanche', 'Shigar', 'Kharmang', 'Diamer', 'Danyor', 'Aliabad'],
            'Islamabad Capital Territory' => ['Islamabad'],
        ];

        foreach ($states as $state => $cities) {
            self::upsert('states', ['name' => $state, 'country_id' => $pakistanId], [
                'name' => $state,
                'country_id' => $pakistanId,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (! Schema::hasTable('cities')) {
                continue;
            }

            $stateId = DB::table('states')
                ->where('name', $state)
                ->where('country_id', $pakistanId)
                ->value('id');

            foreach ($cities as $city) {
                self::upsert('cities', ['name' => $city, 'state_id' => $stateId], [
                    'name' => $city,
                    'state_id' => $stateId,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private static function seedNamedRows(string $table, array $names, $now): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($names as $name) {
            self::upsert($table, ['name' => $name], [
                'name' => $name,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private static function upsert(string $table, array $where, array $values): void
    {
        $columns = Schema::getColumnListing($table);
        $where = array_intersect_key($where, array_flip($columns));
        $values = array_intersect_key($values, array_flip($columns));

        if ($where === []) {
            return;
        }

        DB::table($table)->updateOrInsert($where, $values);
    }
}
