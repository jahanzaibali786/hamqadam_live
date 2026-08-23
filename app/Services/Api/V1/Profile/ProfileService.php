<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Profile;

use App\Models\Address;
use App\Models\Career;
use App\Models\Education;
use App\Models\Family;
use App\Models\Lifestyle;
use App\Models\Member;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Models\ProfilePrivacySetting;
use App\Models\User;
use App\Services\Api\V1\Concerns\ExecutesInTransaction;
use Carbon\Carbon;

class ProfileService
{
    use ExecutesInTransaction;

    public function __construct(private readonly ProfileCompletionService $completionService)
    {
    }

    public function getProfile(User $user): User
    {
        $this->ensureProfileDefaults($user);
        $this->refreshCompletion($user);

        return $this->loadProfile($user->fresh());
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data) {
            $member = $user->member ?: Member::create(['user_id' => $user->id]);

            $userData = [];
            foreach (['first_name', 'last_name', 'phone'] as $field) {
                if (array_key_exists($field, $data)) {
                    $userData[$field] = $data[$field];
                }
            }

            if ($userData !== []) {
                $user->fill($userData);
                $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $user->save();
            }

            $memberMap = [
                'gender' => 'gender',
                'date_of_birth' => 'birthday',
                'marital_status_id' => 'marital_status_id',
                'children' => 'children',
                'on_behalf' => 'on_behalves_id',
                'annual_salary_range_id' => 'annual_salary_range_id',
                'mother_tongue' => 'mothere_tongue',
                'about_me' => 'introduction',
                'ai_generated_bio' => 'ai_generated_bio',
                'video_introduction' => 'video_introduction',
                'voice_introduction' => 'voice_introduction',
                'travel_preferences' => 'travel_preferences',
                'future_goals' => 'future_goals',
                'hide_profile' => 'hide_profile',
            ];

            foreach ($memberMap as $input => $column) {
                if (array_key_exists($input, $data)) {
                    $member->{$column} = $input === 'date_of_birth' && $data[$input]
                        ? Carbon::parse($data[$input])->toDateString()
                        : $data[$input];
                }
            }

            if (array_key_exists('known_languages', $data)) {
                $member->known_languages = $data['known_languages'] ? json_encode($data['known_languages']) : null;
            }

            // Registration-collected fields that live on `members` directly.
            $extraMemberMap = [
                'annual_income' => 'annual_income',
                'employment_status' => 'employment_status',
                'education_level_id' => 'education_level_id',
                'degree_id' => 'degree_id',
                'field_of_study_id' => 'field_of_study_id',
                'institution_id' => 'institution_id',
                'graduation_year' => 'graduation_year',
                'education_status' => 'education_status',
                'expected_graduation_year' => 'expected_graduation_year',
                'profession_category_id' => 'profession_category_id',
                'profession_id' => 'profession_id',
                'job_title' => 'job_title',
                'organization' => 'organization',
                'years_of_experience' => 'years_of_experience',
                'father_occupation' => 'father_occupation',
                'mother_occupation' => 'mother_occupation',
                'siblings_brothers' => 'siblings_brothers',
                'siblings_sisters' => 'siblings_sisters',
                'family_location' => 'family_location',
                'family_values' => 'family_values',
                'family_country_id' => 'family_country_id',
                'family_state' => 'family_state',
                'family_city' => 'family_city',
                'marriage_timeline' => 'marriage_timeline',
                'willing_to_work_after_marriage' => 'willing_to_work_after_marriage',
                'expects_spouse_to_work' => 'expects_spouse_to_work',
            ];

            foreach ($extraMemberMap as $input => $column) {
                if (array_key_exists($input, $data)) {
                    $member->{$column} = $data[$input];
                }
            }

            // Registration stores hobbies as a comma-separated string
            // (step14 does implode(', ', ...)), so accept either shape.
            if (array_key_exists('hobbies', $data)) {
                $member->hobbies = is_array($data['hobbies'])
                    ? implode(', ', $data['hobbies'])
                    : $data['hobbies'];
            }

            $member->profile_completion_percentage = $this->completionService->calculate($user->fresh());
            $member->save();

            $this->syncRelatedProfileTables($user, $data);

            return $this->getProfile($user);
        });
    }

    /**
     * Write the relation-backed registration fields.
     *
     * Each block targets exactly the table and key that
     * StepwiseRegistrationService writes, so editing a profile and completing
     * registration cannot end up disagreeing about where a value lives:
     *   step 3/6  -> spiritual_backgrounds
     *   step 4    -> addresses (keyed on type = 'present')
     *   step 8    -> education
     *   step 9    -> physical_attributes + lifestyles
     *   step 10   -> careers
     *   step 15   -> families
     *
     * Only keys actually present in the payload are touched, so a partial PUT
     * never blanks out a field the caller did not mention.
     */
    private function syncRelatedProfileTables(User $user, array $data): void
    {
        $pick = function (array $keys) use ($data): array {
            $out = [];
            foreach ($keys as $input => $column) {
                if (array_key_exists($input, $data)) {
                    $out[$column] = $data[$input];
                }
            }

            return $out;
        };

        $spiritual = $pick([
            'religion_id' => 'religion_id',
            'sect_main_id' => 'sect_main_id',
            'school_of_thought_id' => 'school_of_thought_id',
            'tradition_id' => 'tradition_id',
            'caste_id' => 'caste_id',
            'sub_caste_id' => 'sub_caste_id',
        ]);
        if ($spiritual !== []) {
            SpiritualBackground::updateOrCreate(['user_id' => $user->id], $spiritual);
        }

        $address = $pick([
            'country_id' => 'country_id',
            'state_id' => 'state_id',
            'city_id' => 'city_id',
            'area' => 'area',
        ]);
        if ($address !== []) {
            Address::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'present'],
                $address + ['user_id' => $user->id, 'type' => 'present']
            );
        }

        $education = $pick([
            'education_level_id' => 'education_level_id',
            'degree_id' => 'degree_id',
            'field_of_study_id' => 'field_of_study_id',
            'institution_id' => 'institution_id',
            'graduation_year' => 'graduation_year',
            'education_status' => 'education_status',
            'expected_graduation_year' => 'expected_graduation_year',
        ]);
        if ($education !== []) {
            Education::updateOrCreate(['user_id' => $user->id], $education);
        }

        $physical = $pick([
            'height' => 'height',
            'weight' => 'weight',
            'body_type' => 'body_type',
            'complexion' => 'complexion',
            'blood_group' => 'blood_group',
        ]);
        if ($physical !== []) {
            PhysicalAttribute::updateOrCreate(['user_id' => $user->id], $physical);
        }

        if (array_key_exists('diet', $data)) {
            Lifestyle::updateOrCreate(['user_id' => $user->id], ['diet' => $data['diet']]);
        }

        $career = $pick([
            'profession_category_id' => 'profession_category_id',
            'profession_id' => 'profession_id',
            'job_title' => 'designation',
            'organization' => 'company',
            'years_of_experience' => 'years_of_experience',
        ]);
        if ($career !== []) {
            // years_of_experience is NOT NULL with no default in this schema,
            // so a create() without it fails under MySQL strict mode.
            Career::updateOrCreate(
                ['user_id' => $user->id],
                $career + ['years_of_experience' => $career['years_of_experience'] ?? '']
            );
        }

        $family = $pick([
            'father_occupation' => 'father_occupation',
            'mother_occupation' => 'mother_occupation',
            'siblings_brothers' => 'no_of_brothers',
            'siblings_sisters' => 'no_of_sisters',
        ]);
        if ($family !== []) {
            Family::updateOrCreate(['user_id' => $user->id], $family);
        }
    }

    public function updatePrivacy(User $user, array $data): ProfilePrivacySetting
    {
        $privacy = $this->ensurePrivacy($user);
        $privacy->fill($data);
        $privacy->save();

        return $privacy;
    }

    public function updateVisibility(User $user, bool $hidden): User
    {
        $member = $user->member ?: Member::create(['user_id' => $user->id]);
        $member->hide_profile = $hidden;
        $member->save();

        return $this->getProfile($user);
    }

    public function deactivate(User $user): User
    {
        $user->deactivated = 1;
        $user->save();

        return $user;
    }

    private function refreshCompletion(User $user): void
    {
        if (! $user->member) {
            return;
        }

        $user->member->profile_completion_percentage = $this->completionService->calculate($user);
        $user->member->save();
    }

    private function ensureProfileDefaults(User $user): void
    {
        if (! $user->member) {
            Member::create(['user_id' => $user->id]);
        }

        $this->ensurePrivacy($user);
    }

    private function ensurePrivacy(User $user): ProfilePrivacySetting
    {
        return ProfilePrivacySetting::firstOrCreate(['user_id' => $user->id]);
    }

    private function loadProfile(User $user): User
    {
        /*
         * Everything registration collects, so GET /profile can return it.
         * Registration writes across several tables (step 4 -> addresses,
         * step 8 -> education, step 9 -> physical_attributes, step 10 ->
         * careers, step 3/6 -> spiritual_backgrounds, step 13 ->
         * profile_verification_requests), and loading only `member` meant most
         * of the answers the user gave were never returned.
         */
        return $user->load([
            'member',
            'profile_privacy_setting',
            'addresses',
            'education',
            'career',
            'physical_attributes',
            'spiritual_backgrounds',
            'hobbies',
            'families',
            'lifestyles',
            'gallery_images',
        ]);
    }
}

