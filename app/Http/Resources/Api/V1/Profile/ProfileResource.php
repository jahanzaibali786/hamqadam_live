<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /profile — the authenticated member's own profile.
 *
 * Returns everything registration collected. Registration spreads its 18 steps
 * across several tables, so this resource reads from `member` plus the
 * addresses / education / career / physical_attributes /
 * spiritual_backgrounds / hobbies / families relations. See
 * ProfileService::loadProfile() for the eager loads that back this.
 *
 * Existing top-level keys (`user`, `member`, `privacy`) keep their shape and
 * their field names so current app builds keep working; new data arrives under
 * new keys alongside them.
 */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->member;
        $address = $this->addresses;
        $education = $this->education;
        $career = $this->career;
        $physical = $this->physical_attributes;
        $spiritual = $this->spiritual_backgrounds;
        $family = $this->families;
        $lifestyle = $this->lifestyles;

        // addresses/education/career are hasMany on the User model, so take the
        // most recent row; the rest are hasOne.
        $address = $this->firstOf($address);
        $education = $this->firstOf($education);
        $career = $this->firstOf($career);

        return [
            'user' => [
                'id' => $this->id,
                'code' => $this->code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => trim(($this->first_name ?? '').' '.($this->last_name ?? '')),
                'email' => $this->email,
                'phone' => $this->phone,
                'photo' => $this->photo ? uploaded_asset($this->photo) : null,
                'approved' => (bool) $this->approved,
                'blocked' => (bool) $this->blocked,
                'deactivated' => (bool) $this->deactivated,
                'email_verified_at' => optional($this->email_verified_at)->toISOString(),
            ],

            // Unchanged keys, so existing clients are unaffected.
            'member' => [
                'gender' => $member?->gender,
                'date_of_birth' => $member?->birthday ? (string) $member->birthday : null,
                'about_me' => $member?->introduction,
                'ai_generated_bio' => $member?->ai_generated_bio,
                'video_introduction' => $member?->video_introduction,
                'voice_introduction' => $member?->voice_introduction,
                'marital_status_id' => $member?->marital_status_id,
                'children' => $member?->children,
                'on_behalf_id' => $member?->on_behalves_id,
                'annual_salary_range_id' => $member?->annual_salary_range_id,
                'mother_tongue' => $member?->mothere_tongue,
                'known_languages' => $this->jsonList($member?->known_languages),
                'travel_preferences' => $member?->travel_preferences,
                'future_goals' => $member?->future_goals,
                'hide_profile' => (bool) $member?->hide_profile,
                'verification_status' => $member?->verification_status ?? 'unverified',
                'profile_completion_percentage' => (int) ($member?->profile_completion_percentage ?? 0),
            ],

            // ---- registration data that was previously not returned ----

            'religion_and_language' => [
                'religion_id' => $spiritual?->religion_id,
                'sect_main_id' => $spiritual?->sect_main_id,
                'school_of_thought_id' => $spiritual?->school_of_thought_id,
                'tradition_id' => $spiritual?->tradition_id,
                'mother_tongue' => $member?->mothere_tongue,
                'languages_spoken_fluently' => $this->jsonList($member?->languages_spoken_fluently),
                'prayer_frequency' => $member?->prayer_frequency,
                'religious_practice_level' => $member?->religious_practice_level,
                'hijab_beard_preference' => $member?->hijab_beard_preference,
            ],

            'caste' => [
                'caste_id' => $spiritual?->caste_id,
                'sub_caste_id' => $spiritual?->sub_caste_id,
                'community_biradari' => $member?->community_biradari,
                'family_value_id' => $spiritual?->family_value_id,
                'ethnicity' => $spiritual?->ethnicity,
                'personal_value' => $spiritual?->personal_value,
                'community_value' => $spiritual?->community_value,
            ],

            'location' => [
                'country_id' => $address?->country_id,
                'state_id' => $address?->state_id,
                'city_id' => $address?->city_id,
                'area' => $address?->area,
                'address_type' => $address?->type,
                'postal_code' => $address?->postal_code,
                'work_location_city' => $member?->work_location_city,
                'relocation_preference' => $member?->relocation_preference,
                'visa_immigration_status' => $member?->visa_immigration_status,
                'future_living_preference' => $member?->future_living_preference,
            ],

            'education' => [
                'education_level_id' => $education?->education_level_id,
                'education_level' => $member?->education_level,
                'degree_id' => $education?->degree_id,
                // The legacy free-text columns are literally named *_legacy in
                // this schema; `degree` / `institution` do not exist and read
                // back as a silent null.
                'degree' => $education?->degree_legacy,
                'field_of_study_id' => $education?->field_of_study_id,
                'institution_id' => $education?->institution_id,
                'institution' => $education?->institution_legacy,
                'graduation_year' => $education?->graduation_year,
                'education_status' => $education?->education_status,
                'expected_graduation_year' => $education?->expected_graduation_year,
                'is_highest_degree' => $education ? (bool) $education->is_highest_degree : null,
                'currently_studying' => $education ? (bool) $education->present : null,
            ],

            'career' => [
                'profession_category_id' => $career?->profession_category_id,
                'profession_id' => $career?->profession_id,
                'job_title' => $career?->designation,
                'organization' => $career?->company,
                'years_of_experience' => $career?->years_of_experience,
                'currently_working' => $career ? (bool) $career->present : null,
                'employment_status' => $member?->employment_status,
                'annual_income' => $member?->annual_income,
                'annual_salary_range_id' => $member?->annual_salary_range_id,
                'financial_responsibility' => $member?->financial_responsibility,
                'willing_to_work_after_marriage' => $member?->willing_to_work_after_marriage,
                'expects_spouse_to_work' => $member?->expects_spouse_to_work,
            ],

            'physical' => [
                'height' => $physical?->height,
                'weight' => $physical?->weight,
                'body_type' => $physical?->body_type,
                'complexion' => $physical?->complexion,
                'blood_group' => $physical?->blood_group,
                'eye_color' => $physical?->eye_color,
                'hair_color' => $physical?->hair_color,
                'body_art' => $physical?->body_art,
                'disability' => $physical?->disability ?? $member?->disability,
                'health_conditions' => $member?->health_conditions,
                // Registration step 9 writes height to physical_attributes and
                // diet to the separate lifestyles table.
                'diet' => $lifestyle?->diet,
            ],

            'lifestyle_and_interests' => [
                'hobbies' => $this->jsonList($member?->hobbies),
                'interests_multi_select' => $this->jsonList($member?->interests_multi_select),
                'favorite_weekend_activities' => $member?->favorite_weekend_activities,
                'life_values' => $this->jsonList($member?->life_values),
                'love_language' => $this->jsonList($member?->love_language),
                'personality_type' => $member?->personality_type,
                'communication_style' => $member?->communication_style,
                'communication_preferences' => $this->jsonList($member?->communication_preferences),
                'conflict_resolution_style' => $member?->conflict_resolution_style,
                'travel_preferences' => $member?->travel_preferences,
            ],

            /*
             * Family data lives in TWO places: the `families` table (legacy,
             * one row per user) and newer columns on `members`. Prefer the
             * families row and fall back to members, otherwise whichever half
             * the user filled in comes back empty.
             */
            'family' => [
                'father_name' => $family?->father,
                'mother_name' => $family?->mother,
                'father_occupation' => $family?->father_occupation ?? $member?->father_occupation,
                'mother_occupation' => $family?->mother_occupation ?? $member?->mother_occupation,
                'siblings_brothers' => $family?->no_of_brothers ?? $member?->siblings_brothers,
                'siblings_sisters' => $family?->no_of_sisters ?? $member?->siblings_sisters,
                'siblings_note' => $family?->sibling,
                'about_parents' => $family?->about_parents,
                'about_siblings' => $family?->about_siblings,
                'about_relatives' => $family?->about_relatives,
                'married_siblings' => $member?->married_siblings,
                'family_type' => $member?->family_type,
                'family_values' => $member?->family_values,
                'family_location' => $member?->family_location,
                'family_bio' => $member?->family_bio,
                'family_expectations' => $member?->family_expectations,
                'guardian_name' => $member?->guardian_name,
                'guardian_contact' => $member?->guardian_contact,
                'parents_contact' => $member?->parents_contact,
            ],

            'marriage_expectations' => [
                'looking_for' => $member?->looking_for,
                'marriage_timeline' => $member?->marriage_timeline,
                'children_preference' => $member?->children_preference,
                'expectations_after_marriage' => $this->jsonList($member?->expectations_after_marriage),
                'proposal_preferences' => $member?->proposal_preferences,
                'future_goals' => $member?->future_goals,
            ],

            'photos' => [
                'profile_photo' => $this->photo ? uploaded_asset($this->photo) : null,
                'cover_photo' => $member?->cover_photo ? uploaded_asset($member->cover_photo) : null,
                'gallery' => $this->galleryUrls(),
            ],

            // Identity verification: the CNIC/selfie submitted at registration
            // step 13, plus the AI check that runs against them.
            'verification' => [
                'status' => $member?->verification_status ?? 'unverified',
                'ai' => [
                    'status' => $member?->ai_verification_status ?? 'not_started',
                    'recommendation' => $member?->ai_verification_recommendation,
                    'attempts' => (int) ($member?->ai_verification_attempts ?? 0),
                    'verified_at' => optional($member?->ai_verified_at)->toISOString(),
                    'last_attempt_at' => optional($member?->ai_verification_last_attempt_at)->toISOString(),
                ],
            ],

            'registration' => [
                'completion_percentage' => (int) ($member?->profile_completion_percentage ?? 0),
                'steps' => $this->jsonList($member?->registration_steps),
            ],

            'privacy' => new ProfilePrivacyResource($this->profile_privacy_setting),
        ];
    }

    /** hasMany relations come back as collections; take the newest row. */
    private function firstOf(mixed $relation): mixed
    {
        if ($relation instanceof \Illuminate\Support\Collection
            || $relation instanceof \Illuminate\Database\Eloquent\Collection) {
            return $relation->sortByDesc('id')->first();
        }

        return $relation;
    }

    /**
     * These columns are inconsistent in the database: some hold a JSON array,
     * some a comma-separated string (registration does
     * implode(', ', $hobbies)), some are already cast to array by the model.
     * Always hand the client a list.
     */
    private function jsonList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values($value);
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== ''));
    }

    private function galleryUrls(): array
    {
        $images = $this->gallery_images;
        if (! $images) {
            return [];
        }

        return $images
            ->map(fn ($g) => $g->image ? uploaded_asset($g->image) : null)
            ->filter()
            ->values()
            ->all();
    }
}
