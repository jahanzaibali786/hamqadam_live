<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoMatrimonialSeeder extends Seeder
{
    private Carbon $now;

    public function run(): void
    {
        $this->now = now();

        $basicPackageId = $this->basicPackageId();

        $demoUserId = $this->memberUser([
            'first_name' => 'Hamqadam',
            'last_name' => 'Demo',
            'email' => 'demo.user@hamqadam.test',
            'phone' => '+923001110001',
            'gender' => 1,
            'birthday' => '1997-04-15',
            'introduction' => 'Family-oriented software professional looking for a respectful and practicing partner.',
            'approved' => 1,
            'verification_status' => 'verified',
            'package_id' => $basicPackageId,
        ]);

        $ayeshaId = $this->memberUser([
            'first_name' => 'Ayesha',
            'last_name' => 'Khan',
            'email' => 'ayesha.khan@hamqadam.test',
            'phone' => '+923001110002',
            'gender' => 2,
            'birthday' => '1999-02-08',
            'introduction' => 'Teacher from Lahore who values faith, kindness, education, and family involvement.',
            'approved' => 1,
            'verification_status' => 'verified',
            'package_id' => $basicPackageId,
        ]);

        $fatimaId = $this->memberUser([
            'first_name' => 'Fatima',
            'last_name' => 'Ahmed',
            'email' => 'fatima.ahmed@hamqadam.test',
            'phone' => '+923001110003',
            'gender' => 2,
            'birthday' => '1998-11-20',
            'introduction' => 'Doctor from Islamabad seeking a sincere match with balanced religious and career values.',
            'approved' => 1,
            'verification_status' => 'verified',
            'package_id' => $basicPackageId,
        ]);

        $sanaId = $this->memberUser([
            'first_name' => 'Sana',
            'last_name' => 'Malik',
            'email' => 'sana.malik@hamqadam.test',
            'phone' => '+923001110004',
            'gender' => 2,
            'birthday' => '2000-06-12',
            'introduction' => 'Designer from Karachi, optimistic, family-focused, and interested in shared growth.',
            'approved' => 1,
            'verification_status' => 'submitted',
            'package_id' => $basicPackageId,
        ]);

        $this->verification($demoUserId, 'approved', '35202-1234567-1');
        $this->verification($ayeshaId, 'approved', '35202-1234567-2');
        $this->verification($fatimaId, 'approved', '61101-1234567-3');
        $this->verification($sanaId, 'submitted', '42101-1234567-4');

        $this->interest($demoUserId, $ayeshaId, 0, 'I liked your profile and family values.');
        $this->interest($fatimaId, $demoUserId, 0, 'Your profile looks compatible with my preferences.');
        $this->interest($sanaId, $demoUserId, 1, 'Accepted for family discussion.');

        $this->match($demoUserId, $ayeshaId, 91, [
            'Age is within your preferred range.',
            'Religious and family values are strongly aligned.',
            'Education and lifestyle preferences are compatible.',
        ]);
        $this->match($demoUserId, $fatimaId, 84, [
            'Both profiles value education and professional stability.',
            'Location preference is acceptable.',
            'Partner preference and lifestyle signals match well.',
        ]);
        $this->match($demoUserId, $sanaId, 76, [
            'Good lifestyle and communication compatibility.',
            'Some location flexibility may be required.',
            'Profile is recently active and has enough signals.',
        ]);
    }

    private function basicPackageId(): int
    {
        $package = DB::table('packages')->where('name', 'Basic Free')->first();

        $payload = [
            'plan_tier' => 'free',
            'express_interest' => 25,
            'photo_gallery' => 5,
            'contact' => 5,
            'profile_viewers_view' => 10,
            'profile_image_view' => 10,
            'gallery_image_view' => 10,
            'auto_profile_match' => 1,
            'auto_horoscope_profile_match' => 0,
            'price' => 0,
            'active' => 1,
            'validity' => 365,
            'feature_flags' => json_encode(['ai_matching' => true, 'advanced_search' => true]),
            'is_recurring' => 0,
            'updated_at' => $this->now,
        ];

        if ($package) {
            DB::table('packages')->where('id', $package->id)->update($payload);

            return (int) $package->id;
        }

        $payload['name'] = 'Basic Free';
        $payload['created_at'] = $this->now;

        return (int) DB::table('packages')->insertGetId($payload);
    }

    private function memberUser(array $data): int
    {
        $user = DB::table('users')->where('email', $data['email'])->first();
        $userPayload = [
            'user_type' => 'member',
            'code' => $user->code ?? $this->demoCode($data['email']),
            'membership' => 1,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => $data['first_name'].' '.$data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'email_verified_at' => $this->now,
            'password' => Hash::make('password'),
            'approved' => $data['approved'],
            'blocked' => 0,
            'deactivated' => 0,
            'permanently_delete' => 0,
            'verification_info' => json_encode([
                ['label' => 'CNIC', 'value' => 'Seeded demo verification'],
            ]),
            'updated_at' => $this->now,
        ];

        if ($user) {
            DB::table('users')->where('id', $user->id)->update($userPayload);
            $userId = (int) $user->id;
        } else {
            $userPayload['created_at'] = $this->now;
            $userId = (int) DB::table('users')->insertGetId($userPayload);
        }

        DB::table('members')->updateOrInsert(
            ['user_id' => $userId],
            [
                'gender' => $data['gender'],
                'birthday' => $data['birthday'],
                'introduction' => $data['introduction'],
                'marital_status_id' => 1,
                'children' => 0,
                'mothere_tongue' => 1,
                'known_languages' => json_encode([1]),
                'travel_preferences' => 'Open to relocation within Pakistan.',
                'future_goals' => 'Build a stable, faith-centered family life.',
                'profile_completion_percentage' => 100,
                'hide_profile' => 0,
                'verification_status' => $data['verification_status'],
                'current_package_id' => $data['package_id'],
                'remaining_interest' => 25,
                'remaining_contact_view' => 5,
                'remaining_profile_viewer_view' => 10,
                'remaining_profile_image_view' => 10,
                'remaining_gallery_image_view' => 10,
                'remaining_photo_gallery' => 5,
                'auto_profile_match' => 1,
                'auto_horoscope_profile_match' => 0,
                'package_validity' => $this->now->copy()->addYear()->toDateString(),
                'updated_at' => $this->now,
                'created_at' => $this->now,
            ]
        );

        $this->profileSections($userId, (int) $data['gender']);

        return $userId;
    }

    private function profileSections(int $userId, int $gender): void
    {
        DB::table('addresses')->updateOrInsert(
            ['user_id' => $userId, 'type' => 'present'],
            ['country_id' => 166, 'state_id' => 2728, 'city_id' => $gender === 1 ? 85569 : 85568, 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('education')->updateOrInsert(
            ['user_id' => $userId, 'degree' => 'BS'],
            ['institution' => 'University of Punjab', 'start' => 2015, 'end' => 2019, 'present' => 0, 'is_highest_degree' => 1, 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('careers')->updateOrInsert(
            ['user_id' => $userId, 'designation' => $gender === 1 ? 'Software Engineer' : 'Teacher'],
            ['company' => $gender === 1 ? 'Lahore Tech Hub' : 'Beaconhouse', 'start' => 2020, 'end' => null, 'present' => 1, 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('physical_attributes')->updateOrInsert(
            ['user_id' => $userId],
            ['height' => $gender === 1 ? 1.75 : 1.62, 'weight' => $gender === 1 ? 72 : 56, 'complexion' => 'Fair', 'body_type' => 'Average', 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('lifestyles')->updateOrInsert(
            ['user_id' => $userId],
            ['diet' => 'halal', 'drink' => 'no', 'smoke' => 'no', 'living_with' => 'family', 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('families')->updateOrInsert(
            ['user_id' => $userId],
            ['father' => 'Father', 'father_occupation' => 'Business', 'mother' => 'Mother', 'mother_occupation' => 'Homemaker', 'sibling' => 3, 'no_of_sisters' => 1, 'no_of_brothers' => 2, 'about_parents' => 'Respectable Pakistani family.', 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('spiritual_backgrounds')->updateOrInsert(
            ['user_id' => $userId],
            ['religion_id' => 1, 'caste_id' => null, 'sub_caste_id' => null, 'ethnicity' => 'Pakistani', 'personal_value' => 'Religious', 'family_value_id' => 1, 'community_value' => 'Family-oriented', 'updated_at' => $this->now, 'created_at' => $this->now]
        );

        DB::table('partner_expectations')->updateOrInsert(
            ['user_id' => $userId],
            [
                'general' => 'Practicing, respectful, educated, and family-oriented.',
                'preferred_age_min' => $gender === 1 ? 22 : 27,
                'preferred_age_max' => $gender === 1 ? 30 : 35,
                'height_min' => $gender === 1 ? 1.50 : 1.65,
                'height_max' => $gender === 1 ? 1.75 : 1.90,
                'religion_id' => 1,
                'education' => 'Graduate',
                'profession' => 'Any respectable profession',
                'smoking_acceptable' => 0,
                'drinking_acceptable' => 0,
                'diet' => 'halal',
                'lifestyle' => 'family-oriented',
                'prayer' => 'regular',
                'religious_practice' => 'practicing',
                'deal_breakers' => json_encode(['smoking', 'dishonesty']),
                'updated_at' => $this->now,
                'created_at' => $this->now,
            ]
        );
    }

    private function verification(int $userId, string $status, string $cnic): void
    {
        DB::table('profile_verification_requests')->updateOrInsert(
            ['user_id' => $userId],
            [
                'status' => $status,
                'cnic_number' => $cnic,
                'face_match_status' => $status === 'approved' ? 'matched' : 'pending',
                'face_match_score' => $status === 'approved' ? 94.50 : null,
                'submitted_at' => $this->now->copy()->subDays(2),
                'reviewed_at' => $status === 'approved' ? $this->now->copy()->subDay() : null,
                'updated_at' => $this->now,
                'created_at' => $this->now,
            ]
        );
    }

    private function interest(int $senderId, int $receiverId, int $status, string $note): void
    {
        DB::table('express_interests')->updateOrInsert(
            ['user_id' => $receiverId, 'interested_by' => $senderId],
            [
                'status' => $status,
                'initial_note' => $note,
                'responded_at' => $status === 1 ? $this->now->copy()->subHours(6) : null,
                'expires_at' => $this->now->copy()->addDays(14),
                'compatibility_snapshot' => $status === 1 ? 78 : 82,
                'updated_at' => $this->now,
                'created_at' => $this->now,
            ]
        );
    }

    private function match(int $userId, int $matchId, int $percentage, array $reasons): void
    {
        DB::table('profile_matches')->updateOrInsert(
            ['user_id' => $userId, 'match_id' => $matchId],
            [
                'match_percentage' => $percentage,
                'score_breakdown' => json_encode([
                    'partner_preference' => 35,
                    'religion' => 15,
                    'location' => 8,
                    'education_profession' => 10,
                    'lifestyle' => 9,
                    'activity' => 5,
                ]),
                'compatibility_reasons' => json_encode($reasons),
                'compatibility_explanation' => implode(' ', $reasons),
                'calculated_at' => $this->now,
                'updated_at' => $this->now,
                'created_at' => $this->now,
            ]
        );
    }

    private function demoCode(string $email): string
    {
        return 'DEMO'.strtoupper(substr(sha1($email), 0, 8));
    }
}
