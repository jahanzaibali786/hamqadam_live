<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Address;
use App\Models\AnnualSalaryRange;
use App\Models\Career;
use App\Models\Education;
use App\Models\Lifestyle;
use App\Models\PartnerExpectation;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use App\Models\Upload;
use App\Models\ProfileVerificationRequest;
use App\Models\ProfileVerificationDocument;
use App\Models\GalleryImage;
use App\Models\Family;

class RegistrationOnboarding
{
    public static function rules(bool $required = true): array
    {
        $prefix = $required ? 'required' : 'nullable';

        return [
            // Step 2 - Basic Profile
            'marital_status_id' => [$prefix, 'integer'],
            'children' => ['nullable', 'string', 'max:50'],
            'mother_tongue' => [$prefix, 'integer'],
            'known_languages' => [$prefix, 'array'],
            'known_languages.*' => ['integer'],
            'height' => [$prefix, 'numeric', 'between:0,9.99'],
            'weight' => [$prefix, 'numeric', 'between:0,999.99'],
            'complexion' => [$prefix, 'string', 'max:50'],
            'disability' => ['nullable', 'string', 'max:255'],

            // Step 3 - About Me & Personality
            'about_me' => [$prefix, 'string', 'max:2000'],
            'looking_for' => ['nullable', 'string', 'max:2000'],
            'life_values' => ['nullable', 'array'],
            'life_values.*' => ['string', 'max:100'],
            'personality_type' => ['nullable', 'string', 'max:50'],
            'communication_style' => ['nullable', 'string', 'max:50'],
            'love_language' => ['nullable', 'array'],
            'love_language.*' => ['string', 'max:100'],
            'conflict_resolution_style' => ['nullable', 'string', 'max:50'],

            // Step 4 - Religion & Culture
            'religion_id' => [$prefix, 'integer'],
            'caste_id' => ['nullable', 'integer'],
            'sub_caste_id' => ['nullable', 'integer'],
            'personal_value' => [$prefix, 'string', 'max:100'],
            'religious_practice_level' => ['nullable', 'string', 'max:50'],
            'prayer_frequency' => ['nullable', 'string', 'max:50'],
            'community_biradari' => ['nullable', 'string', 'max:100'],

            // Step 5 - Education & Career
            'education_level' => ['nullable', 'string', 'max:100'], // Keep for backward compatibility but nullable
            'education_degree' => ['nullable', 'string', 'max:255'], // Made nullable for backward compatibility
            'education_institution' => ['nullable', 'string', 'max:255'], // Made nullable for backward compatibility
            'profession' => ['nullable', 'string', 'max:255'], // Made nullable for backward compatibility
            'company' => ['nullable', 'string', 'max:255'], // Made nullable for backward compatibility
            'employment_status' => ['nullable', 'string', 'max:20'],
            'annual_salary_range_id' => ['nullable', 'integer'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
            'work_location_city' => ['nullable', 'string', 'max:255'],
            // New controlled fields for profession (now primary required fields)
            'profession_category_id' => ['nullable', 'integer', 'exists:profession_categories,id'],
            'profession_id' => ['nullable', 'integer', 'exists:professions,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            // New controlled fields for education (now primary required fields)
            'education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'field_of_study_id' => ['nullable', 'integer', 'exists:fields_of_study,id'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'education_status' => ['nullable', 'string', 'in:completed,in_progress,dropped'],
            'expected_graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],

            // Step 6 - Family Details
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'family_type' => ['nullable', 'string', 'max:50'],
            'siblings_brothers' => ['nullable', 'integer', 'min:0'],
            'siblings_sisters' => ['nullable', 'integer', 'min:0'],
            'married_siblings' => ['nullable', 'integer', 'min:0'],
            'family_location' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:100'],
            'family_bio' => ['nullable', 'string', 'max:2000'],
            'family_expectations' => ['nullable', 'string', 'max:2000'],
            'parents_contact' => ['nullable', 'string', 'max:100'],

            // Step 7 - Marriage & Future Plans
            'children_preference' => ['nullable', 'string', 'max:50'],
            'relocation_preference' => ['nullable', 'string', 'max:50'],
            'visa_immigration_status' => ['nullable', 'string', 'max:50'],
            'future_living_preference' => ['nullable', 'string', 'max:50'],
            'financial_responsibility' => ['nullable', 'string', 'max:50'],
            'marriage_timeline' => ['nullable', 'string', 'max:50'],
            'expectations_after_marriage' => ['nullable', 'array'],
            'expectations_after_marriage.*' => ['string', 'max:100'],
            'willing_to_work_after_marriage' => ['nullable', 'in:yes,no,depends,depends_on_mutual_understanding'],
            'expects_spouse_to_work' => ['nullable', 'in:yes,no,depends,depends_on_mutual_understanding'],

            // Step 8 - Lifestyle & Interests
            'diet' => [$prefix, 'string', 'max:50'],
            'smoke' => [$prefix, 'string', 'max:20'],
            'drink' => [$prefix, 'string', 'max:20'],
            'living_with' => [$prefix, 'string', 'max:100'],
            'hobbies' => ['nullable', 'string', 'max:2000'],
            'interests_multi_select' => ['nullable', 'array'],
            'interests_multi_select.*' => ['string', 'max:100'],
            'future_goals' => [$prefix, 'string', 'max:1000'],
            'health_conditions' => ['nullable', 'string', 'max:1000'],
            'languages_spoken_fluently' => ['nullable', 'array'],
            'languages_spoken_fluently.*' => ['string', 'max:100'],
            'favorite_weekend_activities' => ['nullable', 'string', 'max:255'],
            'proposal_preferences' => ['nullable', 'string', 'max:100'],
            'communication_preferences' => ['nullable', 'array'],
            'communication_preferences.*' => ['string', 'max:50'],

            // Step 10 - Partner Preferences
            'country_id' => [$prefix, 'integer'],
            'state_id' => [$prefix, 'integer'],
            'city_id' => [$prefix, 'integer'],
            'partner_age_min' => [$prefix, 'integer', 'min:18', 'max:100'],
            'partner_age_max' => [$prefix, 'integer', 'min:18', 'max:100', 'gte:partner_age_min'],
            'partner_religion_id' => [$prefix, 'integer'],
            'partner_language_id' => [$prefix, 'integer'],
            'partner_education' => [$prefix, 'string', 'max:255'],
            'partner_profession' => [$prefix, 'string', 'max:255'],
            'partner_lifestyle' => [$prefix, 'string', 'max:100'],
            'partner_prayer' => [$prefix, 'string', 'max:100'],
            'partner_country_id' => [$prefix, 'integer'],
            'partner_state_id' => [$prefix, 'integer'],
            'partner_city_id' => [$prefix, 'integer'],
            'partner_income_min' => ['nullable', 'numeric', 'min:0'],
            'partner_income_max' => ['nullable', 'numeric', 'min:0', 'gte:partner_income_min'],
            'deal_breakers' => ['nullable', 'array'],
            'deal_breakers.*' => ['string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'live_with_family' => ['nullable', 'in:yes,no'],
            'family_country_id' => ['nullable', 'integer'],
            'family_state' => ['nullable', 'string', 'max:255'],
            'family_city' => ['nullable', 'string', 'max:255'],
            'partner_height_min' => ['nullable', 'numeric', 'between:0,9.99'],
            'partner_height_max' => ['nullable', 'numeric', 'between:0,9.99'],
            'partner_marital_status_id' => ['nullable', 'integer'],
            'partner_caste_id' => ['nullable', 'integer'],
            'cnic_number' => [$prefix, 'string', 'max:50'],
            'profile_photo' => [$prefix, 'image', 'max:5120'],
            'additional_photos' => [$prefix, 'array', 'min:2', 'max:4'],
            'additional_photos.*' => ['image', 'max:5120'],
            'cnic_front' => [$prefix, 'image', 'max:5120'],
            'cnic_back' => [$prefix, 'image', 'max:5120'],
            'selfie_verification' => [$prefix, 'image', 'max:5120'],
        ];
    }

    public static function persist(User $user, array $data): void
    {
        $member = $user->member;
        if ($member) {
            $member->marital_status_id = $data['marital_status_id'] ?? $member->marital_status_id;
            $member->children = $data['children'] ?? $member->children;
            $member->mothere_tongue = $data['mother_tongue'] ?? $member->mothere_tongue;
            $member->known_languages = isset($data['known_languages']) ? json_encode($data['known_languages']) : $member->known_languages;
            $member->introduction = $data['about_me'] ?? $member->introduction;
            $member->future_goals = $data['future_goals'] ?? $member->future_goals;

            // Step 5 - Education & Career
            $member->employment_status = $data['employment_status'] ?? $member->employment_status;
            $member->work_location_city = $data['work_location_city'] ?? $member->work_location_city;
            $member->annual_salary_range_id = $data['annual_salary_range_id'] ?? $member->annual_salary_range_id;
            $member->annual_income = $data['annual_income'] ?? ($data['annual_salary_range_id'] ? (AnnualSalaryRange::find($data['annual_salary_range_id'])?->max_salary ?? $member->annual_income) : $member->annual_income);
            
            // New profession fields
            $member->profession_category_id = $data['profession_category_id'] ?? $member->profession_category_id;
            $member->profession_id = $data['profession_id'] ?? $member->profession_id;
            $member->job_title = $data['job_title'] ?? $member->job_title;
            $member->organization = $data['organization'] ?? $member->organization;
            $member->years_of_experience = $data['years_of_experience'] ?? $member->years_of_experience;
            
            // New education fields
            $member->education_level_id = $data['education_level_id'] ?? $member->education_level_id;
            $member->degree_id = $data['degree_id'] ?? $member->degree_id;
            $member->field_of_study_id = $data['field_of_study_id'] ?? $member->field_of_study_id;
            $member->institution_id = $data['institution_id'] ?? $member->institution_id;
            $member->graduation_year = $data['graduation_year'] ?? $member->graduation_year;
            $member->education_status = $data['education_status'] ?? $member->education_status;
            $member->expected_graduation_year = $data['expected_graduation_year'] ?? $member->expected_graduation_year;

            // Step 3 - About Me & Personality
            $member->looking_for = $data['looking_for'] ?? $member->looking_for;
            $member->life_values = isset($data['life_values']) ? json_encode($data['life_values']) : $member->life_values;
            $member->personality_type = $data['personality_type'] ?? $member->personality_type;
            $member->communication_style = $data['communication_style'] ?? $member->communication_style;
            $member->love_language = isset($data['love_language']) ? json_encode($data['love_language']) : $member->love_language;
            $member->conflict_resolution_style = $data['conflict_resolution_style'] ?? $member->conflict_resolution_style;

            // Step 4 - Religion & Culture
            $member->religious_practice_level = $data['religious_practice_level'] ?? $member->religious_practice_level;
            $member->prayer_frequency = $data['prayer_frequency'] ?? $member->prayer_frequency;
            $member->community_biradari = $data['community_biradari'] ?? $member->community_biradari;
            
            // New sect fields
            $member->sect_main_id = $data['sect_main_id'] ?? $member->sect_main_id;
            $member->school_of_thought_id = $data['school_of_thought_id'] ?? $member->school_of_thought_id;
            $member->tradition_id = $data['tradition_id'] ?? $member->tradition_id;

            // Step 5 - Education & Career
            $member->employment_status = $data['employment_status'] ?? $member->employment_status;
            $member->work_location_city = $data['work_location_city'] ?? $member->work_location_city;
            $member->annual_salary_range_id = $data['annual_salary_range_id'] ?? $member->annual_salary_range_id;
            $member->annual_income = $data['annual_income'] ?? ($data['annual_salary_range_id'] ? (AnnualSalaryRange::find($data['annual_salary_range_id'])?->max_salary ?? $member->annual_income) : $member->annual_income);

            // Step 6 - Family Details
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

            // Step 7 - Marriage & Future Plans
            $member->children_preference = $data['children_preference'] ?? $member->children_preference;
            $member->relocation_preference = $data['relocation_preference'] ?? $member->relocation_preference;
            $member->visa_immigration_status = $data['visa_immigration_status'] ?? $member->visa_immigration_status;
            $member->future_living_preference = $data['future_living_preference'] ?? $member->future_living_preference;
            $member->financial_responsibility = $data['financial_responsibility'] ?? $member->financial_responsibility;
            $member->marriage_timeline = $data['marriage_timeline'] ?? $member->marriage_timeline;
            $member->expectations_after_marriage = isset($data['expectations_after_marriage']) ? json_encode($data['expectations_after_marriage']) : $member->expectations_after_marriage;
            $member->willing_to_work_after_marriage = array_key_exists('willing_to_work_after_marriage', $data)
                ? self::nullableBooleanChoice($data['willing_to_work_after_marriage'])
                : $member->willing_to_work_after_marriage;
            $member->expects_spouse_to_work = array_key_exists('expects_spouse_to_work', $data)
                ? self::nullableBooleanChoice($data['expects_spouse_to_work'])
                : $member->expects_spouse_to_work;

            // Step 8 - Lifestyle & Interests
            $member->disability = $data['disability'] ?? $member->disability;
            $member->hobbies = $data['hobbies'] ?? $member->hobbies;
            $member->interests_multi_select = isset($data['interests_multi_select']) ? json_encode($data['interests_multi_select']) : $member->interests_multi_select;
            $member->health_conditions = $data['health_conditions'] ?? $member->health_conditions;
            $member->languages_spoken_fluently = isset($data['languages_spoken_fluently']) ? json_encode($data['languages_spoken_fluently']) : $member->languages_spoken_fluently;
            $member->favorite_weekend_activities = $data['favorite_weekend_activities'] ?? $member->favorite_weekend_activities;
            $member->proposal_preferences = $data['proposal_preferences'] ?? $member->proposal_preferences;
            $member->communication_preferences = isset($data['communication_preferences']) ? json_encode($data['communication_preferences']) : $member->communication_preferences;

            if (! empty($data['cnic_number'])) {
                $member->verification_status = 'submitted';
            }
            if (Schema::hasColumn('members', 'registration_steps')) {
                $member->registration_steps = self::registrationSnapshot($data);
            }
            if (Schema::hasColumn('members', 'registration_completed_at')) {
                $member->registration_completed_at = now();
            }
            $member->profile_completion_percentage = 100;
            $member->save();
        }

        self::saveModel(PhysicalAttribute::class, $user->id, [
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'complexion' => $data['complexion'] ?? null,
        ]);

        self::saveModel(Lifestyle::class, $user->id, [
            'diet' => $data['diet'] ?? null,
            'smoke' => $data['smoke'] ?? null,
            'drink' => $data['drink'] ?? null,
            'living_with' => $data['living_with'] ?? null,
        ]);

        self::saveModel(SpiritualBackground::class, $user->id, [
            'religion_id' => $data['religion_id'] ?? null,
            'caste_id' => $data['caste_id'] ?? null,
            'sub_caste_id' => $data['sub_caste_id'] ?? null,
            'personal_value' => $data['personal_value'] ?? null,
            // New sect fields
            'sect_main_id' => $data['sect_main_id'] ?? null,
            'school_of_thought_id' => $data['school_of_thought_id'] ?? null,
            'tradition_id' => $data['tradition_id'] ?? null,
        ]);

        $presentAddress = [
            'type' => 'present',
            'country_id' => $data['country_id'] ?? null,
            'state_id' => $data['state_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
        ];
        if (Schema::hasColumn('addresses', 'area')) {
            $presentAddress['area'] = $data['area'] ?? null;
        }
        self::saveModel(Address::class, $user->id, $presentAddress, ['type' => 'present']);

        self::saveModel(Education::class, $user->id, [
            'degree' => $data['education_degree'] ?? null,
            'institution' => $data['education_institution'] ?? null,
            'start' => now()->year,
            // New controlled fields
            'education_level_id' => $data['education_level_id'] ?? null,
            'degree_id' => $data['degree_id'] ?? null,
            'field_of_study_id' => $data['field_of_study_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'education_status' => $data['education_status'] ?? null,
            'expected_graduation_year' => $data['expected_graduation_year'] ?? null,
        ]);

        self::saveModel(Career::class, $user->id, [
            'designation' => $data['profession'] ?? null,
            'company' => $data['company'] ?? null,
            'start' => now()->year,
            // New controlled fields
            'profession_category_id' => $data['profession_category_id'] ?? null,
            'profession_id' => $data['profession_id'] ?? null,
        ]);

        self::saveModel(Family::class, $user->id, [
            'father_occupation' => $data['father_occupation'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'no_of_sisters' => $data['siblings_sisters'] ?? null,
            'no_of_brothers' => $data['siblings_brothers'] ?? null,
            'about_parents' => $data['family_bio'] ?? ($data['family_location'] ?? null),
            'about_siblings' => isset($data['siblings_brothers'], $data['siblings_sisters']) ? ('Brothers: ' . $data['siblings_brothers'] . ', Sisters: ' . $data['siblings_sisters']) : null,
            'about_relatives' => $data['family_expectations'] ?? null,
        ]);

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
                'language_id' => $data['partner_language_id'] ?? null,
                'preferred_language_ids' => isset($data['partner_language_id']) ? [$data['partner_language_id']] : null,
                'education' => $data['partner_education'] ?? null,
                'profession' => $data['partner_profession'] ?? null,
                'lifestyle' => $data['partner_lifestyle'] ?? null,
                'prayer' => $data['partner_prayer'] ?? null,
                'preferred_country_id' => $data['partner_country_id'] ?? null,
                'preferred_state_id' => $data['partner_state_id'] ?? null,
                'preferred_city_id' => $data['partner_city_id'] ?? null,
                'income_min' => $data['partner_income_min'] ?? null,
                'income_max' => $data['partner_income_max'] ?? null,
                'diet' => $data['diet'] ?? null,
                'deal_breakers' => self::normalizeDealBreakers($data['deal_breakers'] ?? null),
            ]
        );

        self::persistUploads($user, $data);
        self::persistVerification($user, $data);
    }

    private static function persistUploads(User $user, array $data): void
    {
        if (($data['profile_photo'] ?? null) instanceof UploadedFile) {
            $user->photo = self::storeUpload($data['profile_photo'], $user->id);
            $user->photo_approved = 1;
            $user->save();
        }

        foreach (self::uploadedFiles($data['additional_photos'] ?? []) as $file) {
            $uploadId = self::storeUpload($file, $user->id);
            GalleryImage::firstOrCreate([
                'user_id' => $user->id,
                'image' => $uploadId,
            ]);
        }
    }

    private static function persistVerification(User $user, array $data): void
    {
        if (empty($data['cnic_number']) && empty($data['cnic_front']) && empty($data['cnic_back']) && empty($data['selfie_verification'])) {
            return;
        }

        $labels = [
            'cnic_number' => 'CNIC Number',
            'cnic_front' => 'CNIC Front',
            'cnic_back' => 'CNIC Back',
            'selfie_verification' => 'Selfie Verification',
        ];
        $verificationInfo = [];
        if (! empty($data['cnic_number'])) {
            $verificationInfo[] = ['type' => 'text', 'label' => $labels['cnic_number'], 'value' => $data['cnic_number']];
        }

        $verification = ProfileVerificationRequest::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'submitted',
                'cnic_number' => $data['cnic_number'] ?? null,
                'face_match_status' => 'pending',
                'submitted_at' => now(),
            ]
        );

        foreach (['cnic_front' => 'cnic_front', 'cnic_back' => 'cnic_back', 'selfie_verification' => 'selfie'] as $field => $type) {
            $file = $data[$field] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $uploadId = self::storeUpload($file, $user->id, 'uploads/verification_form');
            $upload = Upload::find($uploadId);
            $verificationInfo[] = ['type' => 'file', 'label' => $labels[$field], 'value' => $upload?->file_name];
            ProfileVerificationDocument::updateOrCreate(
                ['profile_verification_request_id' => $verification->id, 'type' => $type],
                ['upload_id' => $uploadId, 'file_path' => $upload?->file_name, 'metadata' => ['source' => 'web_registration']]
            );
        }

        $user->verification_info = json_encode($verificationInfo);
        $user->save();
        $user->member?->forceFill(['verification_status' => 'submitted'])->save();
    }

    private static function storeUpload(UploadedFile $file, int $userId, string $directory = 'uploads/all'): int
    {
        $upload = new Upload();
        $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $upload->file_name = $file->store($directory);
        $upload->user_id = $userId;
        $upload->extension = $file->getClientOriginalExtension();
        $upload->type = str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'file';
        $upload->file_size = $file->getSize();
        $upload->save();

        return (int) $upload->id;
    }

    private static function uploadedFiles(mixed $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
    }

    private static function normalizeDealBreakers(mixed $value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));
        }
        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }

        return null;
    }

    private static function registrationSnapshot(array $data): string
    {
        $blocked = ['password', 'password_confirmation', 'profile_photo', 'additional_photos', 'cnic_front', 'cnic_back', 'selfie_verification'];
        $snapshot = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $blocked, true) || $value instanceof UploadedFile) {
                continue;
            }
            if (is_array($value)) {
                $snapshot[$key] = array_values(array_filter($value, fn ($item) => ! $item instanceof UploadedFile));
            } else {
                $snapshot[$key] = $value;
            }
        }

        return json_encode($snapshot);
    }

    private static function saveModel(string $modelClass, int $userId, array $attributes, array $extraKeys = []): void
    {
        $model = $modelClass::where('user_id', $userId);
        foreach ($extraKeys as $key => $value) {
            $model->where($key, $value);
        }

        $record = $model->first() ?: new $modelClass();
        $record->user_id = $userId;
        foreach (array_merge($extraKeys, array_filter($attributes, fn ($value) => $value !== null)) as $key => $value) {
            $record->{$key} = $value;
        }
        $record->save();
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
