<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'marital_status_id' => ['sometimes', 'nullable', 'integer'],
            'children' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'on_behalf' => ['sometimes', 'nullable', 'integer'],
            'annual_salary_range_id' => ['sometimes', 'nullable', 'integer'],
            'mother_tongue' => ['sometimes', 'nullable', 'integer'],
            'known_languages' => ['sometimes', 'nullable', 'array'],
            'known_languages.*' => ['integer'],
            'about_me' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'ai_generated_bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'video_introduction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'voice_introduction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'travel_preferences' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'future_goals' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'hide_profile' => ['sometimes', 'boolean'],
            /*
             * Registration collects roughly sixty fields across 18 steps but
             * this endpoint only accepted eighteen, so most of what a member
             * answered at signup could never be edited afterwards. Everything
             * below writes to the SAME table registration writes to - see
             * StepwiseRegistrationService::step3..step16.
             */

            // step 3 - religion & language -> spiritual_backgrounds / members
            'religion_id' => ['sometimes', 'nullable', 'integer'],
            'sect_main_id' => ['sometimes', 'nullable', 'integer'],
            'school_of_thought_id' => ['sometimes', 'nullable', 'integer'],
            'tradition_id' => ['sometimes', 'nullable', 'integer'],

            // step 4 - location -> addresses (type = present)
            'country_id' => ['sometimes', 'nullable', 'integer'],
            'state_id' => ['sometimes', 'nullable', 'integer'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'area' => ['sometimes', 'nullable', 'string', 'max:255'],

            // step 6 - caste -> spiritual_backgrounds
            'caste_id' => ['sometimes', 'nullable', 'integer'],
            'sub_caste_id' => ['sometimes', 'nullable', 'integer'],

            // step 8 - education -> education + members
            'education_level_id' => ['sometimes', 'nullable', 'integer'],
            'degree_id' => ['sometimes', 'nullable', 'integer'],
            'field_of_study_id' => ['sometimes', 'nullable', 'integer'],
            'institution_id' => ['sometimes', 'nullable', 'integer'],
            'graduation_year' => ['sometimes', 'nullable', 'integer', 'min:1950', 'max:2100'],
            'education_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'expected_graduation_year' => ['sometimes', 'nullable', 'integer', 'min:1950', 'max:2100'],

            // step 9 - physical -> physical_attributes / lifestyles
            'height' => ['sometimes', 'nullable'],
            'weight' => ['sometimes', 'nullable'],
            'body_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
            'diet' => ['sometimes', 'nullable', 'string', 'max:50'],

            // step 10 - career & income -> careers + members
            'profession_category_id' => ['sometimes', 'nullable', 'integer'],
            'profession_id' => ['sometimes', 'nullable', 'integer'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'organization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'years_of_experience' => ['sometimes', 'nullable', 'string', 'max:50'],
            'annual_income' => ['sometimes', 'nullable'],
            'employment_status' => ['sometimes', 'nullable', 'string', 'max:50'],

            // step 14 - interests
            'hobbies' => ['sometimes', 'nullable'],

            // step 15/16 - family -> families + members
            'father_occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'siblings_brothers' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'siblings_sisters' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'family_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'family_values' => ['sometimes', 'nullable', 'string', 'max:255'],
            'family_country_id' => ['sometimes', 'nullable', 'integer'],
            'family_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'family_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'marriage_timeline' => ['sometimes', 'nullable', 'string', 'max:100'],
            'willing_to_work_after_marriage' => ['sometimes', 'nullable', 'boolean'],
            'expects_spouse_to_work' => ['sometimes', 'nullable', 'boolean'],        ];
    }
}

