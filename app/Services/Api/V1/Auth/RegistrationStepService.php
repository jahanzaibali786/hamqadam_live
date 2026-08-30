<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Models\Address;
use App\Models\AnnualSalaryRange;
use App\Models\Career;
use App\Models\Education;
use App\Models\Lifestyle;
use App\Models\Member;
use App\Models\PartnerExpectation;
use App\Models\PhysicalAttribute;
use App\Models\ProfilePrivacySetting;
use App\Models\SpiritualBackground;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrationStepService
{
    public const STEPS = [
        'step1' => 'Account Details',
        'step2' => 'Basic Profile',
        'step3' => 'About Me & Personality',
        'step4' => 'Religion & Culture',
        'step5' => 'Education & Career',
        'step6' => 'Family Details',
        'step7' => 'Marriage & Future Plans',
        'step8' => 'Lifestyle & Interests',
        'step9' => 'Profile Media',
        'step10' => 'Partner Preferences',
        'step11' => 'Verification',
        'step12' => 'Privacy & Account Settings',
    ];

    public function step1(User $user, array $data): void
    {
        $user->forceFill([
            'gender' => $data['gender'] ?? $user->gender,
            'date_of_birth' => isset($data['date_of_birth']) ? Carbon::parse($data['date_of_birth']) : $user->date_of_birth,
        ])->save();
    }

    public function step2(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $member = $user->member;
            if ($member) {
                $member->gender = $data['gender'] ?? $member->gender;
                $member->birthday = isset($data['date_of_birth'])
                    ? Carbon::parse($data['date_of_birth'])->toDateString()
                    : $member->birthday;
                $member->marital_status_id = $data['marital_status_id'] ?? $member->marital_status_id;
                $member->children = $data['children'] ?? $member->children;
                $member->mothere_tongue = $data['mother_tongue'] ?? $member->mothere_tongue;
                $member->known_languages = isset($data['known_languages'])
                    ? json_encode($data['known_languages']) : $member->known_languages;
                $member->disability = $data['disability'] ?? $member->disability;
                $member->save();
            }

            if (isset($data['height']) || isset($data['weight'])) {
                PhysicalAttribute::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'height' => $data['height'] ?? null,
                        'weight' => $data['weight'] ?? null,
                    ]
                );
            }

            if (isset($data['country_id'])) {
                Address::updateOrCreate(
                    ['user_id' => $user->id, 'type' => 'present'],
                    [
                        'country_id' => $data['country_id'] ?? null,
                        'state_id' => $data['state_id'] ?? null,
                        'city_id' => $data['city_id'] ?? null,
                    ]
                );
            }
        });
    }

    public function step3(User $user, array $data): void
    {
        $member = $user->member;
        if (! $member) {
            return;
        }

        $member->introduction = $data['about_me'] ?? $member->introduction;
        $member->looking_for = $data['looking_for'] ?? $member->looking_for;
        $member->life_values = isset($data['life_values']) ? json_encode($data['life_values']) : $member->life_values;
        $member->personality_type = $data['personality_type'] ?? $member->personality_type;
        $member->communication_style = $data['communication_style'] ?? $member->communication_style;
        $member->love_language = isset($data['love_language']) ? json_encode($data['love_language']) : $member->love_language;
        $member->conflict_resolution_style = $data['conflict_resolution_style'] ?? $member->conflict_resolution_style;
        $member->save();
    }

    public function step4(User $user, array $data): void
    {
        $member = $user->member;
        if ($member) {
            $member->religious_practice_level = $data['religious_practice_level'] ?? $member->religious_practice_level;
            $member->prayer_frequency = $data['prayer_frequency'] ?? $member->prayer_frequency;
            $member->community_biradari = $data['community_biradari'] ?? $member->community_biradari;
            $member->hijab_beard_preference = $data['hijab_beard_preference'] ?? $member->hijab_beard_preference;
            $member->save();
        }

        SpiritualBackground::updateOrCreate(
            ['user_id' => $user->id],
            [
                'religion_id' => $data['religion_id'] ?? null,
                'caste_id' => $data['caste_id'] ?? null,
                'sub_caste_id' => $data['sub_caste_id'] ?? null,
                'personal_value' => $data['personal_value'] ?? null,
            ]
        );
    }

    public function step5(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $member = $user->member;
            if ($member) {
                $member->education_level = $data['education_level'] ?? $member->education_level;
                $member->employment_status = $data['employment_status'] ?? $member->employment_status;
                $member->work_location_city = $data['work_location_city'] ?? $member->work_location_city;
                $member->annual_salary_range_id = $data['annual_salary_range_id'] ?? $member->annual_salary_range_id;
                $member->annual_income = $data['annual_income'] ?? ($data['annual_salary_range_id'] ? (AnnualSalaryRange::find($data['annual_salary_range_id'])?->max_salary ?? $member->annual_income) : $member->annual_income);
                $member->save();
            }

            Education::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'degree' => $data['degree'] ?? null,
                    'institution' => $data['institution'] ?? null,
                    'start' => $data['education_start'] ?? now()->year,
                ]
            );

            Career::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'designation' => $data['profession'] ?? null,
                    'company' => $data['company'] ?? null,
                    'start' => $data['career_start'] ?? now()->year,
                ]
            );
        });
    }

    public function step6(User $user, array $data): void
    {
        $member = $user->member;
        if (! $member) {
            return;
        }

        $member->father_occupation = $data['father_occupation'] ?? $member->father_occupation;
        $member->mother_occupation = $data['mother_occupation'] ?? $member->mother_occupation;
        $member->family_type = $data['family_type'] ?? $member->family_type;
        $member->siblings_brothers = $data['siblings_brothers'] ?? $member->siblings_brothers;
        $member->siblings_sisters = $data['siblings_sisters'] ?? $member->siblings_sisters;
        $member->married_siblings = $data['married_siblings'] ?? $member->married_siblings;
        $member->family_location = $data['family_location'] ?? $member->family_location;
        $member->guardian_name = $data['guardian_name'] ?? $member->guardian_name;
        $member->guardian_contact = $data['guardian_contact'] ?? $member->guardian_contact;
        $member->family_bio = $data['family_bio'] ?? $member->family_bio;
        $member->family_expectations = $data['family_expectations'] ?? $member->family_expectations;
        $member->parents_contact = $data['parents_contact'] ?? $member->parents_contact;
        $member->save();
    }

    public function step7(User $user, array $data): void
    {
        $member = $user->member;
        if (! $member) {
            return;
        }

        $member->children_preference = $data['children_preference'] ?? $member->children_preference;
        $member->relocation_preference = $data['relocation_preference'] ?? $member->relocation_preference;
        $member->visa_immigration_status = $data['visa_immigration_status'] ?? $member->visa_immigration_status;
        $member->future_living_preference = $data['future_living_preference'] ?? $member->future_living_preference;
        $member->financial_responsibility = $data['financial_responsibility'] ?? $member->financial_responsibility;
        $member->marriage_timeline = $data['marriage_timeline'] ?? $member->marriage_timeline;
        $member->expectations_after_marriage = isset($data['expectations_after_marriage'])
            ? json_encode($data['expectations_after_marriage']) : $member->expectations_after_marriage;
        $member->willing_to_work_after_marriage = self::nullableBooleanChoice($data['willing_to_work_after_marriage'] ?? $member->willing_to_work_after_marriage);
        $member->expects_spouse_to_work = self::nullableBooleanChoice($data['expects_spouse_to_work'] ?? $member->expects_spouse_to_work);
        $member->save();
    }

    public function step8(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $member = $user->member;
            if ($member) {
                $member->hobbies = $data['hobbies'] ?? $member->hobbies;
                $member->interests_multi_select = isset($data['interests_multi_select'])
                    ? json_encode($data['interests_multi_select']) : $member->interests_multi_select;
                $member->travel_preferences = $data['travel_preferences'] ?? $member->travel_preferences;
                $member->future_goals = $data['future_goals'] ?? $member->future_goals;
                $member->health_conditions = $data['health_conditions'] ?? $member->health_conditions;
                $member->languages_spoken_fluently = isset($data['languages_spoken_fluently'])
                    ? json_encode($data['languages_spoken_fluently']) : $member->languages_spoken_fluently;
                $member->favorite_weekend_activities = $data['favorite_weekend_activities'] ?? $member->favorite_weekend_activities;
                $member->proposal_preferences = $data['proposal_preferences'] ?? $member->proposal_preferences;
                $member->communication_preferences = isset($data['communication_preferences'])
                    ? json_encode($data['communication_preferences']) : $member->communication_preferences;
                $member->save();
            }

            Lifestyle::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'diet' => $data['diet'] ?? null,
                    'smoke' => $data['smoke'] ?? null,
                    'drink' => $data['drink'] ?? null,
                    'living_with' => $data['living_with'] ?? null,
                ]
            );
        });
    }

    public function step9(User $user, array $data): void
    {
        $member = $user->member;
        if (! $member) {
            return;
        }

        if (isset($data['profile_photo'])) {
            $user->photo = $data['profile_photo'];
            $user->save();
        }

        $member->cover_photo = $data['cover_photo'] ?? $member->cover_photo;
        $member->video_introduction = $data['video_introduction'] ?? $member->video_introduction;
        $member->voice_introduction = $data['voice_introduction'] ?? $member->voice_introduction;
        $member->private_gallery = isset($data['private_gallery'])
            ? json_encode($data['private_gallery']) : $member->private_gallery;
        $member->save();
    }

    public function step10(User $user, array $data): void
    {
        PartnerExpectation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'preferred_age_min' => $data['partner_age_min'] ?? null,
                'preferred_age_max' => $data['partner_age_max'] ?? null,
                'height_min' => $data['partner_height_min'] ?? null,
                'height_max' => $data['partner_height_max'] ?? null,
                'marital_status_id' => $data['partner_marital_status_id'] ?? null,
                'religion_id' => $data['partner_religion_id'] ?? null,
                'caste_id' => $data['partner_caste_id'] ?? null,
                'sub_caste_id' => $data['partner_sub_caste_id'] ?? null,
                'education' => $data['partner_education'] ?? null,
                'profession' => $data['partner_profession'] ?? null,
                'income_min' => $data['partner_income_min'] ?? null,
                'income_max' => $data['partner_income_max'] ?? null,
                'language_id' => $data['partner_language_id'] ?? null,
                'preferred_language_ids' => isset($data['partner_language_ids'])
                    ? json_encode($data['partner_language_ids']) : null,
                'preferred_country_id' => $data['partner_country_id'] ?? null,
                'preferred_state_id' => $data['partner_state_id'] ?? null,
                'preferred_city_id' => $data['partner_city_id'] ?? null,
                'lifestyle' => $data['partner_lifestyle'] ?? null,
                'prayer' => $data['partner_prayer'] ?? null,
                'religious_practice' => $data['partner_religious_practice'] ?? null,
                'body_type' => $data['partner_body_type'] ?? null,
                'complexion' => $data['partner_complexion'] ?? null,
                'children_preference' => $data['partner_children_preference'] ?? null,
                'children_acceptable' => $data['partner_children_acceptable'] ?? null,
                'smoking_acceptable' => $data['partner_smoking_acceptable'] ?? null,
                'drinking_acceptable' => $data['partner_drinking_acceptable'] ?? null,
                'diet' => $data['partner_diet'] ?? null,
                'personal_value' => $data['partner_personal_value'] ?? null,
                'family_value_id' => $data['partner_family_value_id'] ?? null,
                'deal_breakers' => isset($data['deal_breakers'])
                    ? json_encode($data['deal_breakers']) : null,
                'general' => $data['partner_general'] ?? null,
            ]
        );
    }

    public function step11(User $user, array $data): void
    {
        $user->forceFill([
            'photo_approved' => 1,
        ])->save();

        // Verification is handled by the existing verification system
    }

    public function step12(User $user, array $data): void
    {
        $member = $user->member;
        if ($member && isset($data['profile_visibility'])) {
            $member->hide_profile = $data['profile_visibility'] === 'hidden';
            $member->save();
        }

        ProfilePrivacySetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'show_photo' => $data['show_photo'] ?? true,
                'show_gallery' => $data['show_gallery'] ?? true,
                'show_contact' => $data['show_contact'] ?? true,
                'show_email' => $data['show_email'] ?? true,
                'show_phone' => $data['show_phone'] ?? true,
                'show_location' => $data['show_location'] ?? true,
                'allow_profile_view_notifications' => $data['allow_profile_view_notifications'] ?? true,
                'do_not_disturb' => $data['do_not_disturb'] ?? false,
                'invisible_mode' => $data['invisible_mode'] ?? false,
            ]
        );
    }

    private static function nullableBooleanChoice(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'yes', 'true' => true,
            '0', 'no', 'false' => false,
            'depends', 'depends_on_mutual_understanding' => null,
            default => null,
        };
    }
}
