<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Auth;

use App\Dto\Auth\DeviceData;
use App\Dto\Auth\IssuedTokenData;
use App\Models\Address;
use App\Models\Career;
use App\Models\Education;
use App\Models\Family;
use App\Models\GalleryImage;
use App\Models\Lifestyle;
use App\Models\Member;
use App\Models\PartnerExpectation;
use App\Models\PhysicalAttribute;
use App\Models\ProfileVerificationDocument;
use App\Models\ProfileVerificationRequest;
use App\Models\SpiritualBackground;
use App\Models\User;
use App\Services\Api\V1\Profile\ProfileCompletionService;
use App\Support\RegistrationReward;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StepwiseRegistrationService
{
    public const TOTAL_STEPS = 18;

    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly ProfileCompletionService $completion,
    ) {
    }

    public function definitions(): array
    {
        return [
            1 => [
                'key' => 'step1',
                'name' => 'Account For',
                'skippable' => false,
                'fields' => ['on_behalf', 'gender', 'marriage_timeline', 'willing_to_work_after_marriage', 'expects_spouse_to_work'],
                'options' => [
                    'on_behalf' => [1, 2, 3, 4, 5, 6, 7], // Self, Son, Daughter, Brother, Sister, Friend, Relative
                    'gender' => [1, 2], // Male, Female
                    'marriage_timeline' => ['immediate', 'within_3_months', 'within_6_months', 'within_1_year'],
                    'willing_to_work_after_marriage' => ['yes', 'no', 'depends_on_mutual_understanding'],
                    'expects_spouse_to_work' => ['yes', 'no', 'depends_on_mutual_understanding'],
                ],
            ],
            2 => ['key' => 'step2', 'name' => 'Basic Information', 'skippable' => false, 'fields' => ['full_name', 'date_of_birth']],
            3 => ['key' => 'step3', 'name' => 'Religion & Language', 'skippable' => false, 'fields' => ['religion_id', 'mother_tongue', 'sect_main_id', 'school_of_thought_id', 'tradition_id']],
            4 => ['key' => 'step4', 'name' => 'Location', 'skippable' => false, 'fields' => ['country_id', 'state_id', 'city_id', 'area']],
            5 => ['key' => 'step5', 'name' => 'Contact Information', 'skippable' => false, 'fields' => ['country_code', 'phone', 'email']],
            6 => ['key' => 'step6', 'name' => 'Caste', 'skippable' => false, 'fields' => ['caste_id', 'sub_caste_id']],
            7 => ['key' => 'step7', 'name' => 'Marital Status', 'skippable' => false, 'fields' => ['marital_status_id']],
            8 => ['key' => 'step8', 'name' => 'Education', 'skippable' => false, 'fields' => ['education_level_id', 'degree_id', 'field_of_study_id', 'institution_id', 'graduation_year', 'education_status', 'expected_graduation_year']],
            9 => ['key' => 'step9', 'name' => 'Physical Information', 'skippable' => false, 'fields' => ['height', 'diet']],
            10 => ['key' => 'step10', 'name' => 'Career & Income', 'skippable' => false, 'fields' => ['annual_income', 'employment_status', 'profession_category_id', 'profession_id', 'job_title', 'organization', 'years_of_experience']],
            11 => ['key' => 'step11', 'name' => 'Photos', 'skippable' => false, 'fields' => ['profile_photo', 'additional_photos']],
            12 => ['key' => 'step12', 'name' => 'About Yourself', 'skippable' => false, 'fields' => ['about_me']],
            13 => ['key' => 'step13', 'name' => 'Identity Verification', 'skippable' => false, 'fields' => ['cnic_number', 'cnic_front', 'cnic_back', 'selfie_verification']],
            14 => ['key' => 'step14', 'name' => 'Interests & Hobbies', 'skippable' => true, 'fields' => ['hobbies']],
            15 => ['key' => 'step15', 'name' => 'Family Information', 'skippable' => true, 'fields' => ['father_occupation', 'mother_occupation', 'siblings_sisters', 'siblings_brothers']],
            16 => ['key' => 'step16', 'name' => 'Family Details', 'skippable' => true, 'fields' => ['family_location', 'live_with_family', 'family_values', 'family_country_id', 'family_state', 'family_city']],
            17 => ['key' => 'step17', 'name' => 'Basic Partner Preferences', 'skippable' => false, 'fields' => [
                'partner_age_min', 'partner_age_max', 'partner_height_min', 'partner_height_max',
                'partner_marital_status_id', 'partner_religion_id', 'partner_caste_id', 'partner_language_id',
                'partner_country_id', 'partner_state_id', 'partner_city_id', 'partner_education',
                'partner_profession', 'partner_income_min', 'partner_income_max', 'deal_breakers',
            ]],
            18 => ['key' => 'step18', 'name' => 'Account Security', 'skippable' => false, 'fields' => ['email_verify', 'password', 'password_confirmation']],
        ];
    }

    public function start(array $data, DeviceData $deviceData): IssuedTokenData
    {
        $this->validate(1, $data);

        return DB::transaction(function () use ($data, $deviceData): IssuedTokenData {
            $user = User::create([
                'user_type' => 'member',
                'code' => unique_code(),
                'membership' => 0,
                'first_name' => 'Draft',
                'last_name' => 'Member',
                'name' => 'Draft Member',
                'password' => Hash::make(str()->random(40)),
                'approved' => 0,
            ]);

            $member = Member::create([
                'user_id' => $user->id,
                'on_behalves_id' => $data['account_for_id'] ?? null,
                'gender' => $this->resolveGender($data),
                'verification_status' => 'draft',
            ]);

            $this->applyStep($user->fresh('member'), 1, $data);
            $this->markCompleted($member->fresh(), 1);

            return $this->tokens->issue($user->fresh('member'), $deviceData);
        });
    }

    public function saveStep(User $user, int $step, array $data, Request $request): array
    {
        $this->validate($step, $data);

        DB::transaction(function () use ($user, $step, $data, $request): void {
            $user->loadMissing('member');
            $this->applyStep($user, $step, $data, $request);
            $this->markCompleted($user->member, $step);

            if ($step === 18 && $this->mandatoryStepsComplete($user->fresh('member'))) {
                $this->finalize($user->fresh('member'));
            }
        });

        return $this->status($user->fresh(['member', 'partner_expectations']));
    }

    public function completeRegistration(array $data, DeviceData $deviceData, ?Request $request = null): IssuedTokenData
    {
        $this->validate(1, $data);

        return DB::transaction(function () use ($data, $deviceData, $request): IssuedTokenData {
            $user = User::create([
                'user_type' => 'member',
                'code' => unique_code(),
                'membership' => 0,
                'first_name' => 'Draft',
                'last_name' => 'Member',
                'name' => 'Draft Member',
                'password' => Hash::make(str()->random(40)),
                'approved' => 0,
            ]);

            $member = Member::create([
                'user_id' => $user->id,
                'on_behalves_id' => $data['on_behalf'] ?? null,
                'gender' => $this->resolveGender($data),
                'verification_status' => 'draft',
            ]);

            for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
                $this->validate($step, $data);
                $this->applyStep($user, $step, $data, $request);
                $this->markCompleted($member->fresh(), $step);
            }

            return $this->tokens->issue($user->fresh('member'), $deviceData);
        });
    }

    public function status(User $user): array
    {
        $user->loadMissing('member');
        $completed = $this->completedSteps($user->member);

        return [
            'total_steps' => self::TOTAL_STEPS,
            'completed_steps' => $completed,
            'next_step' => $this->nextStep($completed),
            'mandatory_steps' => array_values(array_map(
                fn (int $step): string => 'step'.$step,
                array_filter(array_keys($this->definitions()), fn (int $step): bool => ! $this->definitions()[$step]['skippable'])
            )),
            'optional_steps' => ['step14', 'step15', 'step16'],
            'steps' => array_values($this->definitions()),
            'profile_completion_percentage' => $user->member ? $this->completion->calculate($user) : 0,
            'registration_completed' => (bool) $user->member?->registration_completed_at,
            'registration_completed_at' => $user->member?->registration_completed_at
                ? Carbon::parse($user->member->registration_completed_at)->toISOString()
                : null,
        ];
    }

    private function applyStep(User $user, int $step, array $data, ?Request $request = null): void
    {
        match ($step) {
            1 => $this->step1($user, $data),
            2 => $this->step2($user, $data),
            3 => $this->step3($user, $data),
            4 => $this->step4($user, $data),
            5 => $this->step5($user, $data),
            6 => $this->step6($user, $data),
            7 => $this->step7($user, $data),
            8 => $this->step8($user, $data),
            9 => $this->step9($user, $data),
            10 => $this->step10($user, $data),
            11 => $this->step11($user, $data),
            12 => $this->step12($user, $data),
            13 => $this->step13($user, $data, $request),
            14 => $this->step14($user, $data),
            15 => $this->step15($user, $data),
            16 => $this->step16($user, $data),
            17 => $this->step17($user, $data),
            18 => $this->step18($user, $data),
            default => throw ValidationException::withMessages(['step' => ['Invalid registration step.']]),
        };
    }

    private function step1(User $user, array $data): void
    {
        $this->memberUpdate($user, [
            'on_behalves_id' => $data['on_behalf'] ?? $user->member?->on_behalves_id,
            'gender' => $data['gender'] ?? $user->member?->gender,
            'marriage_timeline' => $data['marriage_timeline'],
            'willing_to_work_after_marriage' => $this->normalizeNullableBoolean($data['willing_to_work_after_marriage'] ?? null),
            'expects_spouse_to_work' => $this->normalizeNullableBoolean($data['expects_spouse_to_work'] ?? null),
        ]);
    }

    private function step2(User $user, array $data): void
    {
        [$first, $last] = $this->splitName($data['full_name']);
        $this->userUpdate($user, ['first_name' => $first, 'last_name' => $last, 'name' => $data['full_name']]);
        $this->memberUpdate($user, ['birthday' => Carbon::parse($data['date_of_birth'])->toDateString()]);
    }

    private function step3(User $user, array $data): void
    {
        SpiritualBackground::updateOrCreate(['user_id' => $user->id], [
            'religion_id' => $data['religion_id'],
            'sect_main_id' => $data['sect_main_id'] ?? null,
            'school_of_thought_id' => $data['school_of_thought_id'] ?? null,
            'tradition_id' => $data['tradition_id'] ?? null,
        ]);
        $this->memberUpdate($user, [
            'mothere_tongue' => $data['mother_tongue'],
            'known_languages' => isset($data['known_languages']) ? json_encode($data['known_languages']) : json_encode([$data['mother_tongue']]),
        ]);
    }

    private function step4(User $user, array $data): void
    {
        $this->upsert('addresses', ['user_id' => $user->id, 'type' => 'present'], [
            'user_id' => $user->id,
            'type' => 'present',
            'country_id' => $data['country_id'],
            'state_id' => $data['state_id'],
            'city_id' => $data['city_id'],
            'area' => $data['area'],
        ]);
    }

    private function step5(User $user, array $data): void
    {
        $this->userUpdate($user, [
            'phone' => trim($data['country_code']).trim($data['phone']),
            'email' => $data['email'],
        ]);
    }

    private function step6(User $user, array $data): void
    {
        SpiritualBackground::updateOrCreate(['user_id' => $user->id], [
            'caste_id' => $data['caste_id'],
            'sub_caste_id' => $data['sub_caste_id'] ?? null,
        ]);
    }

    private function step7(User $user, array $data): void
    {
        $this->memberUpdate($user, ['marital_status_id' => $data['marital_status_id']]);
    }

    private function step8(User $user, array $data): void
    {
        Education::updateOrCreate(['user_id' => $user->id], [
            'education_level_id' => $data['education_level_id'] ?? null,
            'degree_id' => $data['degree_id'] ?? null,
            'field_of_study_id' => $data['field_of_study_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'education_status' => $data['education_status'] ?? null,
            'expected_graduation_year' => $data['expected_graduation_year'] ?? null,
            'start' => $data['education_start'] ?? now()->year,
            'present' => 0,
            'is_highest_degree' => 1,
        ]);
        $this->memberUpdate($user, [
            'education_level_id' => $data['education_level_id'] ?? null,
            'degree_id' => $data['degree_id'] ?? null,
            'field_of_study_id' => $data['field_of_study_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'education_status' => $data['education_status'] ?? null,
            'expected_graduation_year' => $data['expected_graduation_year'] ?? null,
        ]);
    }

    private function step9(User $user, array $data): void
    {
        PhysicalAttribute::updateOrCreate(['user_id' => $user->id], ['height' => $data['height']]);
        Lifestyle::updateOrCreate(['user_id' => $user->id], ['diet' => $data['diet']]);
    }

    private function step10(User $user, array $data): void
    {
        Career::updateOrCreate(['user_id' => $user->id], [
            'profession_category_id' => $data['profession_category_id'] ?? null,
            'profession_id' => $data['profession_id'] ?? null,
            'designation' => $data['job_title'] ?? null,
            'company' => $data['organization'] ?? null,
            'years_of_experience' => $data['years_of_experience'] ?? null,
            'start' => $data['career_start'] ?? now()->year,
            'present' => 1,
        ]);
        $this->memberUpdate($user, [
            'annual_income' => $data['annual_income'],
            'employment_status' => $data['employment_status'],
            'profession_category_id' => $data['profession_category_id'] ?? null,
            'profession_id' => $data['profession_id'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'organization' => $data['organization'] ?? null,
            'years_of_experience' => $data['years_of_experience'] ?? null,
        ]);
    }

    private function step11(User $user, array $data): void
    {
        $profilePhoto = $data['profile_photo'] ?? null;
        if ($profilePhoto && is_string($profilePhoto)) {
            $this->userUpdate($user, ['photo' => $profilePhoto, 'photo_approved' => 0]);
        }

        $additionalPhotos = $data['additional_photos'] ?? [];
        if (is_array($additionalPhotos)) {
            $photoIds = [];
            foreach ($additionalPhotos as $photo) {
                if (is_string($photo)) {
                    $photoIds[] = $photo;
                }
            }
            if (! empty($photoIds)) {
                $this->memberUpdate($user, ['private_gallery' => json_encode($photoIds)]);
                foreach ($photoIds as $photoId) {
                    GalleryImage::updateOrCreate(
                        ['user_id' => $user->id, 'image' => $photoId],
                        ['user_id' => $user->id, 'image' => $photoId]
                    );
                }
            }
        }
    }

    private function step12(User $user, array $data): void
    {
        $this->memberUpdate($user, ['introduction' => $data['about_me']]);
    }

    private function step13(User $user, array $data, ?Request $request): void
    {
        $verification = ProfileVerificationRequest::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'submitted',
                'cnic_number' => $data['cnic_number'] ?? null,
                'face_match_status' => 'pending',
                'submitted_at' => now(),
            ]
        );

        foreach (['cnic_front', 'cnic_back', 'selfie_verification'] as $type) {
            $file = $request?->file($type);
            $payload = ['type' => $type];
            if ($file) {
                $payload['upload_id'] = upload_api_file($file);
            } else {
                $payload['file_path'] = $data[$type];
            }

            ProfileVerificationDocument::updateOrCreate(
                ['profile_verification_request_id' => $verification->id, 'type' => $type],
                $payload
            );
        }

        $this->memberUpdate($user, ['verification_status' => 'submitted']);
    }

    private function step14(User $user, array $data): void
    {
        $this->memberUpdate($user, [
            'hobbies' => $data['hobbies'] ?? null,
        ]);
    }

    private function step15(User $user, array $data): void
    {
        Family::updateOrCreate(['user_id' => $user->id], [
            'father_occupation' => $data['father_occupation'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'no_of_sisters' => $data['siblings_sisters'] ?? null,
            'no_of_brothers' => $data['siblings_brothers'] ?? null,
        ]);
    }

    private function step16(User $user, array $data): void
    {
        $this->memberUpdate($user, [
            'family_location' => $data['family_location'] ?? null,
            'family_values' => $data['family_values'] ?? null,
            'family_country_id' => $data['family_country_id'] ?? null,
            'family_state' => $data['family_state'] ?? null,
            'family_city' => $data['family_city'] ?? null,
        ]);
    }

    private function step17(User $user, array $data): void
    {
        PartnerExpectation::updateOrCreate(['user_id' => $user->id], [
            'preferred_age_min' => $data['partner_age_min'],
            'preferred_age_max' => $data['partner_age_max'],
            'height_min' => $data['partner_height_min'],
            'height_max' => $data['partner_height_max'],
            'marital_status_id' => $data['partner_marital_status_id'],
            'religion_id' => $data['partner_religion_id'],
            'caste_id' => $data['partner_caste_id'],
            'language_id' => $data['partner_language_id'],
            'preferred_country_id' => $data['partner_country_id'],
            'preferred_state_id' => $data['partner_state_id'] ?? null,
            'preferred_city_id' => $data['partner_city_id'] ?? null,
            'education' => $data['partner_education'],
            'profession' => $data['partner_profession'],
            'income_min' => $data['partner_income_min'],
            'income_max' => $data['partner_income_max'],
            'general' => isset($data['deal_breakers']) ? json_encode($data['deal_breakers']) : null,
        ]);
    }

    private function step18(User $user, array $data): void
    {
        $this->userUpdate($user, [
            'email' => $data['email_verify'] ?? $user->email,
            'password' => Hash::make($data['password']),
        ]);
    }

    private function validate(int $step, array $data): void
    {
        Validator::make($data, $this->rules($step))->validate();
    }

    private function rules(int $step): array
    {
        $yesNoDepends = ['yes', 'no', 'depends_on_mutual_understanding'];

        return match ($step) {
            1 => [
                'on_behalf' => ['required', 'integer', 'exists:on_behalves,id'],
                'gender' => ['required', 'integer', Rule::in([1, 2])],
                'marriage_timeline' => ['required', Rule::in($this->definitions()[1]['options']['marriage_timeline'])],
                'willing_to_work_after_marriage' => ['nullable', Rule::in($yesNoDepends)],
                'expects_spouse_to_work' => ['required', Rule::in($yesNoDepends)],
            ],
            2 => ['full_name' => ['required', 'string', 'max:255'], 'date_of_birth' => ['required', 'date', 'before:today']],
            3 => ['religion_id' => ['required', 'integer', 'exists:religions,id'], 'mother_tongue' => ['required', 'integer', 'exists:member_languages,id'], 'sect_main_id' => ['nullable', 'integer', 'exists:sect_main,id'], 'school_of_thought_id' => ['nullable', 'integer', 'exists:school_of_thought,id'], 'tradition_id' => ['nullable', 'integer', 'exists:traditions,id']],
            4 => ['country_id' => ['required', 'integer', 'exists:countries,id'], 'state_id' => ['required', 'integer', 'exists:states,id'], 'city_id' => ['required', 'integer', 'exists:cities,id'], 'area' => ['required', 'string', 'max:255']],
            5 => ['country_code' => ['required', 'string', 'max:10'], 'phone' => ['required', 'string', 'max:30'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(request()->user()?->id)]],
            6 => ['caste_id' => ['required', 'integer', 'exists:castes,id'], 'sub_caste_id' => ['nullable', 'integer', 'exists:sub_castes,id']],
            7 => ['marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id']],
            8 => ['education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'], 'degree_id' => ['nullable', 'integer', 'exists:degrees,id'], 'field_of_study_id' => ['nullable', 'integer', 'exists:fields_of_study,id'], 'institution_id' => ['nullable', 'integer', 'exists:institutions,id'], 'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100'], 'education_status' => ['nullable', Rule::in(['completed', 'in_progress', 'dropped'])], 'expected_graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100']],
            9 => ['height' => ['required', 'numeric', 'between:0,9.99'], 'diet' => ['required', Rule::in(['Vegetarian', 'Non-Vegetarian'])]],
            10 => ['annual_income' => ['required', 'numeric', 'min:0'], 'employment_status' => ['required', Rule::in(['government', 'private', 'civil', 'defence', 'self_employed', 'unemployed', 'retired'])], 'profession_category_id' => ['nullable', 'integer', 'exists:profession_categories,id'], 'profession_id' => ['nullable', 'integer', 'exists:professions,id'], 'job_title' => ['nullable', 'string', 'max:255'], 'organization' => ['nullable', 'string', 'max:255'], 'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:50']],
            11 => ['profile_photo' => ['required', 'image', 'max:5120'], 'additional_photos' => ['nullable', 'array'], 'additional_photos.*' => ['nullable', 'image', 'max:5120']],
            12 => ['about_me' => ['required', 'string', 'max:300']],
            13 => ['cnic_number' => ['required', 'string', 'max:30'], 'cnic_front' => ['required'], 'cnic_back' => ['required'], 'selfie_verification' => ['required']],
            14 => ['hobbies' => ['nullable', 'string', 'max:500']],
            15 => ['father_occupation' => ['nullable', 'string', 'max:255'], 'mother_occupation' => ['nullable', 'string', 'max:255'], 'siblings_sisters' => ['nullable', 'integer', 'min:0'], 'siblings_brothers' => ['nullable', 'integer', 'min:0']],
            16 => ['family_location' => ['nullable', 'string', 'max:255'], 'live_with_family' => ['nullable', Rule::in(['yes', 'no'])], 'family_values' => ['nullable', Rule::in(['Elite', 'High', 'Middle', 'Aspiring', 'Poor'])], 'family_country_id' => ['nullable', 'integer'], 'family_state' => ['nullable', 'string', 'max:255'], 'family_city' => ['nullable', 'string', 'max:255']],
            17 => [
                'partner_age_min' => ['required', 'integer', 'min:18', 'max:100'],
                'partner_age_max' => ['required', 'integer', 'min:18', 'max:100'],
                'partner_height_min' => ['required', 'numeric', 'between:0,9.99'],
                'partner_height_max' => ['required', 'numeric', 'between:0,9.99'],
                'partner_marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
                'partner_religion_id' => ['required', 'integer', 'exists:religions,id'],
                'partner_caste_id' => ['required', 'integer', 'exists:castes,id'],
                'partner_language_id' => ['required', 'integer', 'exists:member_languages,id'],
                'partner_country_id' => ['required', 'integer', 'exists:countries,id'],
                'partner_state_id' => ['nullable', 'integer', 'exists:states,id'],
                'partner_city_id' => ['nullable', 'integer', 'exists:cities,id'],
                'partner_education' => ['nullable', 'string', 'max:255'],
                'partner_profession' => ['nullable', 'string', 'max:255'],
                'partner_income_min' => ['nullable', 'integer', 'min:0'],
                'partner_income_max' => ['nullable', 'integer', 'min:0'],
                'deal_breakers' => ['nullable', 'array'],
            ],
            18 => ['email_verify' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'min:8', 'confirmed']],
            default => ['step' => ['prohibited']],
        };
    }

    private function markCompleted(?Member $member, int $step): void
    {
        if (! $member || ! Schema::hasColumn('members', 'registration_steps')) {
            return;
        }

        $completed = $this->completedSteps($member);
        $key = 'step'.$step;
        if (! in_array($key, $completed, true)) {
            $completed[] = $key;
        }

        $member->forceFill(['registration_steps' => json_encode(array_values($completed))])->save();
    }

    private function completedSteps(?Member $member): array
    {
        if (! $member) {
            return [];
        }

        $steps = $member->registration_steps ?? [];
        if (is_string($steps)) {
            $steps = json_decode($steps, true) ?: [];
        }

        return array_values(array_unique(array_filter((array) $steps)));
    }

    private function nextStep(array $completed): string
    {
        foreach (range(1, self::TOTAL_STEPS) as $step) {
            if (! in_array('step'.$step, $completed, true)) {
                return 'step'.$step;
            }
        }

        return 'completed';
    }

    private function mandatoryStepsComplete(User $user): bool
    {
        $completed = $this->completedSteps($user->member);
        foreach ($this->definitions() as $step => $definition) {
            if (! $definition['skippable'] && ! in_array('step'.$step, $completed, true)) {
                return false;
            }
        }

        return true;
    }

    public function finalize(User $user): void
    {
        $user->forceFill([
            'membership' => 1,
            'approved' => 1,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        RegistrationReward::applyRegistrationDefaultPackage($user);

        $completion = $this->completion->calculate($user->fresh());
        $user->member?->forceFill($this->filterColumns('members', [
            'profile_completion_percentage' => $completion,
            'registration_completed_at' => now(),
        ]))->save();
    }

    private function resolveGender(array $data): ?string
    {
        if (! empty($data['gender'])) {
            return match ((string) $data['gender']) {
                'male' => '1',
                'female' => '2',
                default => (string) $data['gender'],
            };
        }

        return match ($data['account_for'] ?? null) {
            'my_son', 'my_brother' => '1',
            'my_daughter', 'my_sister' => '2',
            default => null,
        };
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?: $name, $parts[1] ?? null];
    }

    private function userUpdate(User $user, array $data): void
    {
        $user->forceFill($this->filterColumns('users', $data))->save();
    }

    private function memberUpdate(User $user, array $data): void
    {
        $user->loadMissing('member');
        $user->member?->forceFill($this->filterColumns('members', $data))->save();
    }

    private function upsert(string $table, array $where, array $data): void
    {
        DB::table($table)->updateOrInsert(
            $this->filterColumns($table, $where),
            $this->filterColumns($table, $data)
        );
    }

    private function filterColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_intersect_key($data, array_flip(Schema::getColumnListing($table)));
    }

    private function normalizeNullableBoolean(mixed $value): ?bool
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
