@php
    $maritalStatuses = \App\Models\MaritalStatus::all();
    $languages = \App\Models\MemberLanguage::all();
    $religions = \App\Models\Religion::all();
    $countries = \App\Models\Country::all();
    $castes = \App\Models\Caste::all();
    $hobbies = \App\Models\Hobby::all();
    
    // New controlled dropdown data
    $professionCategories = \App\Models\ProfessionCategory::where('is_active', true)->orderBy('sort_order')->get();
    $educationLevels = \App\Models\EducationLevel::where('is_active', true)->orderBy('sort_order')->get();
    $institutions = \App\Models\Institution::where('is_active', true)->orderBy('sort_order')->get();
    $annualSalaryRanges = \App\Models\AnnualSalaryRange::orderBy('min_salary', 'asc')->get();
    
    $hobbyOptions = $hobbies->count() > 0 ? $hobbies->pluck('name', 'id')->all() : [
        translate('Reading'), translate('Cooking'), translate('Travel'), translate('Photography'),
        translate('Fitness'), translate('Music'), translate('Movies'), translate('Sports'),
        translate('Gardening'), translate('Painting'), translate('Dancing'), translate('Gaming'),
        translate('Yoga'), translate('Writing'), translate('Hiking'), translate('Volunteering'),
    ];
    $required = '<span class="text-danger">*</span>';
    $pairs = fn($items) => $items->mapWithKeys(fn($item) => [$item->id => $item->name])->all();
    $salaryPairs = $annualSalaryRanges->mapWithKeys(fn($range) => [$range->id => single_price($range->min_salary).' - '.single_price($range->max_salary)])->all();
    $select = fn($label, $name, $options, $required = true, $attrs = [], $col = 'col-lg-6') => compact('label', 'name', 'options', 'required', 'attrs', 'col') + ['type' => 'select'];
    $input = fn($label, $name, $type = 'text', $required = true, $attrs = [], $col = 'col-lg-6') => compact('label', 'name', 'type', 'required', 'attrs', 'col');
    $textarea = fn($label, $name, $required = true, $attrs = [], $col = 'col-lg-12') => compact('label', 'name', 'required', 'attrs', 'col') + ['type' => 'textarea'];
    $yesNoDepends = ['yes' => translate('Yes'), 'no' => translate('No'), 'depends' => translate('Depends on Mutual Understanding')];
    $areaOptionsByCity = [
        'karachi' => ['Clifton','Defence DHA','Gulshan-e-Iqbal','Gulistan-e-Johar','Nazimabad','North Nazimabad','Saddar','Malir','Korangi','Federal B Area','PECHS','Bahadurabad','Other'],
        'lahore' => ['DHA','Gulberg','Model Town','Johar Town','Wapda Town','Garden Town','Iqbal Town','Cantt','Bahria Town','Askari','Other'],
        'islamabad' => ['F-6','F-7','F-8','F-10','F-11','G-6','G-7','G-8','G-9','G-10','G-11','I-8','I-9','Bahria Town','DHA','Other'],
        'rawalpindi' => ['Saddar','Satellite Town','Bahria Town','DHA','Chaklala','Westridge','Gulraiz','Commercial Market','Other'],
        'faisalabad' => ['Madina Town','Peoples Colony','D Ground','Susan Road','Canal Road','Gulberg','Jinnah Colony','Other'],
        'multan' => ['Gulgasht','Cantt','Bosan Road','Shah Rukn-e-Alam','DHA','Wapda Town','Other'],
        'peshawar' => ['Hayatabad','University Town','Cantt','Saddar','Gulbahar','Ring Road','Other'],
        'quetta' => ['Jinnah Town','Cantt','Satellite Town','Samungli Road','Model Town','Other'],
        'hyderabad' => ['Latifabad','Qasimabad','Saddar','Auto Bhan Road','Hirabad','Other'],
        'sialkot' => ['Cantt','Model Town','Paris Road','Daska Road','Other'],
        'gujranwala' => ['Satellite Town','Model Town','Peoples Colony','Wapda Town','DHA','Other'],
    ];    $institutionsByCountry = [
        'pakistan' => ['University of the Punjab','Lahore University of Management Sciences (LUMS)','National University of Sciences and Technology (NUST)','COMSATS University Islamabad','University of Karachi','Aga Khan University','Institute of Business Administration (IBA)','FAST National University','University of Engineering and Technology Lahore','Government College University Lahore','Quaid-i-Azam University','Bahria University','Iqra University','University of Lahore','University of Central Punjab','International Islamic University Islamabad','Dow University of Health Sciences','King Edward Medical University','NED University of Engineering and Technology','Other'],
        'india' => ['University of Delhi','Jawaharlal Nehru University','University of Mumbai','Banaras Hindu University','Indian Institute of Technology','Indian Institute of Management','Aligarh Muslim University','Jamia Millia Islamia','Other'],
        'bangladesh' => ['University of Dhaka','North South University','BRAC University','Bangladesh University of Engineering and Technology','Jahangirnagar University','Other'],
        'united arab emirates' => ['United Arab Emirates University','American University of Sharjah','University of Dubai','Zayed University','Khalifa University','Other'],
        'saudi arabia' => ['King Saud University','King Abdulaziz University','Umm Al-Qura University','Imam Mohammad Ibn Saud Islamic University','King Fahd University of Petroleum and Minerals','Other'],
        'united kingdom' => ['University of Oxford','University of Cambridge','University College London','University of Manchester','University of Birmingham','Imperial College London','Other'],
        'united states' => ['Harvard University','Stanford University','Massachusetts Institute of Technology','University of California','Columbia University','New York University','Other'],
        'canada' => ['University of Toronto','University of British Columbia','McGill University','University of Alberta','University of Waterloo','Other'],
        'australia' => ['University of Melbourne','University of Sydney','Australian National University','Monash University','University of Queensland','Other'],
    ];    $steps = [
        1 => [translate('Account For'), translate('Profile ownership and marriage expectations.'), [
            $select(translate('Who is this profile being created for?'), 'on_behalf', $pairs($on_behalves), true, ['id' => 'on_behalf', 'data-live-search' => 'true'], 'col-lg-12'),
            $select(translate('Gender'), 'gender', [1 => 'Male', 2 => 'Female'], true, ['id' => 'gender'], 'col-lg-6'),
            $select(translate('When are you planning to get married?'), 'marriage_timeline', ['immediate' => translate('Immediate'), 'within_3_months' => translate('Within 3 Months'), 'within_6_months' => translate('Within 6 Months'), 'within_1_year' => translate('1 year')], true, ['id' => 'marriage_timeline']),
            $select(translate('Will you continue working after marriage?'), 'willing_to_work_after_marriage', $yesNoDepends, false, ['data-female-work-input' => '1'], 'col-lg-6'),
            $select(translate('Do you expect your spouse to work after marriage?'), 'expects_spouse_to_work', $yesNoDepends, true),
        ]],
        2 => [translate('Basic Information'), translate('Full name and date of birth.'), [
            $input(translate('Full Name'), 'full_name', 'text', true, ['id' => 'registration_full_name'], 'col-lg-12'),
            $input('', 'date_of_birth', 'hidden', false, ['id' => 'date_of_birth']),
            ['type' => 'date_of_birth_group', 'label' => translate('Date of Birth'), 'required' => true, 'col' => 'col-lg-12'],
        ]],
        3 => [translate('Religion & Language'), translate('Core religious and language information.'), [
            $select(translate('Religion'), 'religion_id', $pairs($religions), true, ['id' => 'registration_religion_id', 'data-live-search' => 'true', 'data-religion' => '1']),
            $select(translate('Mother Language'), 'mother_tongue', $pairs($languages), true, ['id' => 'registration_mother_tongue', 'data-live-search' => 'true']),
            $select(translate('Main Sect'), 'sect_main_id', [], false, ['data-live-search' => 'true', 'data-sect-main' => '1', 'data-selected' => old('sect_main_id')]),
            $select(translate('School of Thought'), 'school_of_thought_id', [], false, ['data-live-search' => 'true', 'data-school-of-thought' => '1', 'data-selected' => old('school_of_thought_id')]),
            $select(translate('Tradition'), 'tradition_id', [], false, ['data-live-search' => 'true', 'data-tradition' => '1', 'data-selected' => old('tradition_id')]),
        ]],
        4 => [translate('Location'), translate('Country, province, city and area.'), [
            $select(translate('Country'), 'country_id', $pairs($countries), true, ['data-live-search' => 'true', 'data-location-country' => 'profile']),
            $select(translate('Province / State'), 'state_id', [], true, ['data-live-search' => 'true', 'data-location-state' => 'profile', 'data-selected' => old('state_id')]),
            $select(translate('City'), 'city_id', [], true, ['data-live-search' => 'true', 'data-location-city' => 'profile', 'data-selected' => old('city_id')]),
            $select(translate('Area'), 'area', [], true, ['data-live-search' => 'true', 'data-location-area' => 'profile', 'data-selected' => old('area')]),
        ]],
        5 => [translate('Contact Information'), translate('Mobile number with country code and unique email.'), [
            $input('', 'country_code', 'hidden', true, ['id' => 'country_code'], 'd-none'),
            $input(translate('Mobile Number'), 'phone', 'tel', true, ['id' => 'phone-code', 'maxlength' => '11', 'inputmode' => 'numeric', 'pattern' => '[0-9]*', 'autocomplete' => 'tel-national'], 'col-lg-12 phone-form-group'),
            $input(translate('Email Address'), 'email', 'email', true, ['id' => 'signinSrEmail', 'data-noverify' => '1'], 'col-lg-12 email-form-group'),
        ]],
        6 => [translate('Caste'), translate('Community details.'), [
            $select(translate('Caste'), 'caste_id', $pairs($castes), true, ['id' => 'registration_caste_id', 'data-live-search' => 'true', 'data-selected' => old('caste_id')]),
            $select(translate('Sub Caste'), 'sub_caste_id', [], false, ['id' => 'registration_sub_caste_id', 'data-live-search' => 'true', 'data-selected' => old('sub_caste_id')]),
        ]],
        7 => [translate('Marital Status'), translate('Current marital status.'), [$select(translate('Marital Status'), 'marital_status_id', $pairs($maritalStatuses), true, [], 'col-lg-12')]],
        8 => [translate('Education'), translate('Highest education and institution.'), [
            $select(translate('Education Level'), 'education_level_id', $pairs($educationLevels), true, ['data-live-search' => 'true', 'data-education-level' => '1', 'data-selected' => old('education_level_id')]),
            $select(translate('Degree'), 'degree_id', [], true, ['data-live-search' => 'true', 'data-degree' => '1', 'data-selected' => old('degree_id')]),
            $select(translate('Field / Major'), 'field_of_study_id', [], false, ['data-live-search' => 'true', 'data-field-of-study' => '1', 'data-selected' => old('field_of_study_id')]),
            $select(translate('Institution'), 'institution_id', $pairs($institutions), true, ['data-live-search' => 'true', 'data-institution' => '1', 'data-selected' => old('institution_id')]),
            $input(translate('Graduation Year'), 'graduation_year', 'number', false, ['min' => '1950', 'max' => '2100']),
            $select(translate('Education Status'), 'education_status', ['completed' => translate('Completed'), 'in_progress' => translate('In Progress'), 'dropped' => translate('Dropped')], false, ['data-education-status' => '1']),
            $input(translate('Expected Graduation Year'), 'expected_graduation_year', 'number', false, ['min' => '1950', 'max' => '2100', 'data-education-status-dependent' => '1']),
        ]],
        9 => [translate('Physical Information'), translate('Height and diet.'), [
            $input(translate('Height'), 'height', 'number', true, ['step' => '0.01', 'min' => '1', 'max' => '9.99', 'placeholder' => translate('Example: 5.08')]),
            $select(translate('Diet'), 'diet', ['Vegetarian' => translate('Vegetarian'), 'Non-Vegetarian' => translate('Non-Vegetarian')], true),
        ]],
        10 => [translate('Career & Income'), translate('Income, work category and profession.'), [
            $select(translate('Annual Income'), 'annual_salary_range_id', $salaryPairs, true, ['data-live-search' => 'true', 'data-selected' => old('annual_salary_range_id')]),
            $select(translate('Employment Status'), 'employment_status', ['government' => translate('Government'), 'private' => translate('Private'), 'civil' => translate('Civil'), 'defence' => translate('Defence'), 'self_employed' => translate('Self-Employed')], true),
            $select(translate('Profession Category'), 'profession_category_id', $pairs($professionCategories), true, ['data-live-search' => 'true', 'data-profession-category' => '1', 'data-selected' => old('profession_category_id')]),
            $select(translate('Profession'), 'profession_id', [], true, ['data-live-search' => 'true', 'data-profession' => '1', 'data-selected' => old('profession_id')]),
            $input(translate('Job Title'), 'job_title', 'text', false, [], 'col-lg-6'),
            $input(translate('Organization'), 'organization', 'text', false, [], 'col-lg-6'),
            $input(translate('Years of Experience'), 'years_of_experience', 'number', false, ['min' => '0', 'max' => '50']),
        ]],
        11 => [translate('Photos'), translate('Upload your profile photo and 2-4 additional photos.'), [
            $input(translate('Profile Photo'), 'profile_photo', 'file', true, ['id' => 'profile_photo', 'accept' => 'image/*', 'data-photo-type' => 'profile'], 'col-lg-12'),
            $input(translate('Additional Photos'), 'additional_photos[]', 'file', true, ['id' => 'additional_photos', 'accept' => 'image/*', 'multiple' => 'multiple', 'data-photo-type' => 'additional'], 'col-lg-12'),
        ]],
        12 => [translate('About Yourself'), translate('Maximum 300 characters.'), [$textarea(translate('About Yourself'), 'about_me', true, ['maxlength' => '300', 'data-about-text' => '1'])]],
        13 => [translate('Identity Verification'), translate('CNIC and selfie verification.'), [$input(translate('CNIC Number'), 'cnic_number', 'text', true, [], 'col-lg-12'), $input(translate('CNIC Front'), 'cnic_front', 'file', true, ['accept' => 'image/*'], 'col-lg-4'), $input(translate('CNIC Back'), 'cnic_back', 'file', true, ['accept' => 'image/*'], 'col-lg-4'), $input(translate('Selfie Verification'), 'selfie_verification', 'selfie_camera', true, [], 'col-lg-4')]],
        14 => [translate('Interests & Hobbies'), translate('Select your interests and hobbies.'), [            ['type' => 'hobby_chips',
            'name' => 'hobbies',
            'label' => translate('Select Your Interests'),
            'required' => false,
            'col' => 'col-lg-12',
            'options' => $hobbyOptions,
        ],
        $input('', 'hobbies', 'hidden', false, ['id' => 'hobbies_hidden']),
    ], true],
        15 => [translate('Family Information'), translate('Optional parent and sibling details.'), [$input(translate("Father's Occupation"), 'father_occupation', 'text', false), $input(translate("Mother's Occupation"), 'mother_occupation', 'text', false), $input(translate('Number of Sisters'), 'siblings_sisters', 'number', false, ['min' => '0']), $input(translate('Number of Brothers'), 'siblings_brothers', 'number', false, ['min' => '0'])], true],
        16 => [translate('Family Details'), translate('Optional family residence and family background.'), [$input(translate('Family Location'), 'family_location', 'text', false), $select(translate('Do you live with your family?'), 'live_with_family', ['yes' => translate('Yes'), 'no' => translate('No')], false), $select(translate('Family Country'), 'family_country_id', $pairs($countries), false, ['data-live-search' => 'true']), $input(translate('Family Province / State'), 'family_state', 'text', false), $input(translate('Family City'), 'family_city', 'text', false)], true],
        17 => [translate('Basic Partner Preferences'), translate('Mandatory preferences used by matching.'), [
            $input(translate('Preferred Age From'), 'partner_age_min', 'number', true, ['min' => '18', 'max' => '100']), $input(translate('Preferred Age To'), 'partner_age_max', 'number', true, ['min' => '18', 'max' => '100']),
            $input(translate('Preferred Height Min'), 'partner_height_min', 'number', true, ['step' => '0.01']), $input(translate('Preferred Height Max'), 'partner_height_max', 'number', true, ['step' => '0.01']),
            $select(translate('Preferred Marital Status'), 'partner_marital_status_id', $pairs($maritalStatuses), true), $select(translate('Preferred Religion'), 'partner_religion_id', $pairs($religions), true, ['data-live-search' => 'true']),
            $select(translate('Preferred Sect / Caste'), 'partner_caste_id', $pairs($castes), true, ['data-live-search' => 'true']), $select(translate('Mother Language'), 'partner_language_id', $pairs($languages), true, ['data-live-search' => 'true']),
            $select(translate('Preferred Country'), 'partner_country_id', $pairs($countries), true, ['data-live-search' => 'true', 'data-location-country' => 'partner']), $select(translate('Preferred Province / State'), 'partner_state_id', [], true, ['data-live-search' => 'true', 'data-location-state' => 'partner', 'data-selected' => old('partner_state_id')]), $select(translate('Preferred City'), 'partner_city_id', [], true, ['data-live-search' => 'true', 'data-location-city' => 'partner', 'data-selected' => old('partner_city_id')]),
            $input(translate('Preferred Education'), 'partner_education'), $input(translate('Preferred Profession'), 'partner_profession'), $input(translate('Preferred Annual Income From'), 'partner_income_min', 'number', false, ['min' => '0']), $input(translate('Preferred Annual Income To'), 'partner_income_max', 'number', false, ['min' => '0']), $textarea(translate('Other Preferences'), 'deal_breakers[]', false),
        ]],
        18 => [translate('Account Security'), translate('Verify your email and create a strong password.'), [
            $input(translate('Email Address'), 'email_verify', 'email', true, ['id' => 'signinSrEmailVerify', 'data-verify' => '1', 'autocomplete' => 'off'], 'col-lg-12'),
            $input(translate('Password'), 'password', 'password', true, ['minlength' => '8']),
            $input(translate('Confirm Password'), 'password_confirmation', 'password', true, ['minlength' => '8']),
        ]],
    ];
@endphp

<div class="registration-flow" data-registration-flow data-total-steps="18">
    <div class="registration-flow-window mb-4">
        @for ($slot = 0; $slot < 3; $slot++)
            <div class="registration-flow-slot" data-step-slot="{{ $slot }}">
                <div class="registration-flow-label" data-step-slot-label></div>
                <div class="registration-flow-track"><span class="registration-flow-circle" data-step-slot-number>{{ $slot + 1 }}</span>@if ($slot < 2)<span class="registration-flow-line"></span>@endif</div>
            </div>
        @endfor
    </div>
    <div class="registration-flow-heading text-center mb-4"><h2 class="fs-18 mb-1" data-registration-heading></h2><div class="small opacity-70" data-registration-subheading></div></div>

    <input type="hidden" name="first_name" id="first_name" value="{{ old('first_name') }}">
    <input type="hidden" name="last_name" id="last_name" value="{{ old('last_name') }}">
    <input type="hidden" name="weight" value="{{ old('weight', 60) }}"><input type="hidden" name="complexion" value="{{ old('complexion', 'Not Specified') }}"><input type="hidden" name="living_with" value="{{ old('living_with', 'Family') }}"><input type="hidden" name="smoke" value="{{ old('smoke', 'No') }}"><input type="hidden" name="drink" value="{{ old('drink', 'No') }}"><input type="hidden" name="personal_value" value="{{ old('personal_value', 'Family Oriented') }}"><input type="hidden" name="company" value="{{ old('company', 'Not Specified') }}"><input type="hidden" name="future_goals" value="{{ old('future_goals', 'Build a peaceful and stable married life.') }}"><input type="hidden" name="children" value="{{ old('children', '0') }}"><input type="hidden" name="partner_lifestyle" value="{{ old('partner_lifestyle', 'Compatible') }}"><input type="hidden" name="partner_prayer" value="{{ old('partner_prayer', 'Flexible') }}"><input type="hidden" name="referral_code" value="{{ old('referral_code') }}">
    <select class="d-none" name="known_languages[]" id="registration_known_language"><option value="{{ old('mother_tongue') }}" selected></option></select>
    <div class="d-none" id="otpInputGroup"><input type="text" name="code" id="verification_code" class="form-control" placeholder="{{ translate('Enter OTP code') }}" maxlength="6"><span id="verifyOtpBtn"></span></div>

    @foreach ($steps as $number => $step)
        <div class="registration-flow-step {{ $number === 1 ? '' : 'd-none' }}" data-registration-step="{{ $number }}" data-step-title="{{ $step[0] }}" data-step-subtitle="{{ $step[1] }}" @if(($step[3] ?? false) === true) data-skippable="1" @endif>
            <div class="row gutters-10">
                @foreach ($step[2] as $field)
                    <div class="{{ $field['col'] ?? 'col-lg-6' }} form-group mb-3" @if(($field['attrs']['data-female-work-input'] ?? null) === '1') data-female-work-field @endif>
                        @if (($field['type'] ?? 'text') !== 'hidden')<label class="form-label">{{ $field['label'] }} @if($field['required'] ?? false){!! $required !!}@endif</label>@endif
                        @if (($field['type'] ?? 'text') === 'select')
                            <select class="form-control aiz-selectpicker {{ $field['attrs']['class'] ?? '' }}" data-container="body" data-boundary="window" name="{{ $field['name'] }}" @if($field['required'] ?? false) required @endif @foreach(($field['attrs'] ?? []) as $attr => $value) @if(!in_array($attr, ['class', 'data-female-work-input'])) {{ $attr }}="{{ $value }}" @endif @endforeach>
                                <option value="">{{ translate('Choose') }}</option>
                                @foreach (($field['options'] ?? []) as $value => $label)<option value="{{ $value }}" @selected(old($field['name'], $field['name'] === 'gender' ? 1 : null) == $value)>{{ $label }}</option>@endforeach
                            </select>
                        @elseif (($field['type'] ?? 'text') === 'textarea')
                            <textarea class="form-control {{ $field['attrs']['class'] ?? '' }}" name="{{ $field['name'] }}" rows="{{ $field['attrs']['rows'] ?? 4 }}" @if($field['required'] ?? false) required @endif @foreach(($field['attrs'] ?? []) as $attr => $value) @if(!in_array($attr, ['class', 'rows'])) {{ $attr }}="{{ $value }}" @endif @endforeach>{{ old(str_replace('[]', '.0', $field['name'])) }}</textarea>
                            @if(($field['attrs']['data-about-text'] ?? null) === '1')<small class="text-muted"><span data-about-counter>0</span>/300</small>@endif
                        @elseif (($field['type'] ?? 'text') === 'hobby_chips')
                            <input type="hidden" name="hobbies" id="hobbies_hidden" value="{{ old('hobbies') }}">
                            <div class="hobby-chips-container d-flex flex-wrap gap-2">
                                @foreach(($field['options'] ?? []) as $id => $name)
                                    <button type="button" class="btn btn-sm hobby-chip border rounded-pill px-3 py-1 mr-2 mb-2" data-value="{{ $id }}" data-selected="false" onclick="toggleHobbyChip(this)">{{ $name }}</button>
                                @endforeach
                            </div>
                        @else
                            @if(($field['attrs']['data-verify'] ?? false) === '1')
                                <div class="input-group">
                                    <input type="{{ $field['type'] ?? 'text' }}" class="form-control {{ $field['attrs']['class'] ?? '' }}" name="{{ $field['name'] }}" value="{{ old('email') }}" @if($field['required'] ?? false) required @endif @foreach(($field['attrs'] ?? []) as $attr => $value) @if(!in_array($attr, ['class', 'data-verify'])) {{ $attr }}="{{ $value }}" @endif @endforeach>
                                    @if(get_setting('registration_verification') == '1')
                                        <button class="btn btn-primary ml-2" type="button" id="sendOtpBtn" onclick="sendVerificationCode(this)">{{ translate('Verify') }}</button>
                                    @endif
                                </div>
                            @elseif(($field['type'] ?? 'text') === 'date_of_birth_group')
                                <div class="row gutters-10">
                                    <div class="col-lg-4 col-4">
                                        <select class="form-control" name="dob_day" id="dob_day" required>
                                            <option value="">{{ translate('Day') }}</option>
                                            @for($d = 1; $d <= 31; $d++)
                                                <option value="{{ str_pad($d, 2, '0', STR_PAD_LEFT) }}">{{ $d }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-4">
                                        <select class="form-control" name="dob_month" id="dob_month" required>
                                            <option value="">{{ translate('Month') }}</option>
                                            @php $monthNames = [translate('Jan'), translate('Feb'), translate('Mar'), translate('Apr'), translate('May'), translate('Jun'), translate('Jul'), translate('Aug'), translate('Sep'), translate('Oct'), translate('Nov'), translate('Dec')]; @endphp
                                            @foreach($monthNames as $i => $m)
                                                <option value="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}">{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-4">
                                        <select class="form-control" name="dob_year" id="dob_year" required>
                                            <option value="">{{ translate('Year') }}</option>
                                            @for($y = (int)date('Y'); $y >= 1940; $y--)
                                                <option value="{{ $y }}">{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            @elseif(($field['type'] ?? 'text') === 'selfie_camera')
                                {{--
                                    Live-camera selfie. A file input let people
                                    upload anything - screenshots, dark photos, a
                                    picture of a picture - and the model then
                                    rejected it with FACE_NOT_DETECTED after
                                    registration was already finished. Capturing
                                    from the camera and checking quality here
                                    means the member fixes it while they can.
                                --}}
                                <div class="selfie-capture border rounded p-2" data-selfie-capture>
                                    <div class="selfie-stage position-relative bg-light rounded overflow-hidden" style="aspect-ratio:3/4">
                                        <video data-selfie-video class="w-100 h-100 d-none" style="object-fit:cover" playsinline muted></video>
                                        <img data-selfie-preview class="w-100 h-100 d-none" style="object-fit:cover" alt="{{ translate('Captured selfie') }}">
                                        <div data-selfie-placeholder class="d-flex flex-column align-items-center justify-content-center h-100 text-center p-3">
                                            <i class="la la-camera" style="font-size:2.4rem;opacity:.45"></i>
                                            <span class="fs-12 opacity-70 mt-2">{{ translate('Use your camera to take a live selfie') }}</span>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-primary mr-2 mb-1" data-selfie-start>
                                            <i class="la la-video"></i> {{ translate('Open camera') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success mr-2 mb-1 d-none" data-selfie-shoot>
                                            <i class="la la-camera"></i> {{ translate('Capture') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1 d-none" data-selfie-retake>
                                            <i class="la la-redo"></i> {{ translate('Retake') }}
                                        </button>
                                        {{--
                                            Upload fallback, for a device with no
                                            camera, a refused permission, or a
                                            camera another app is holding. The
                                            uploaded image goes through exactly
                                            the same brightness / sharpness /
                                            resolution gate as a captured frame -
                                            otherwise this would reopen the
                                            original problem of dark selfies
                                            failing at the model after
                                            registration was already done.
                                        --}}
                                        <button type="button" class="btn btn-sm btn-outline-primary mb-1" data-selfie-upload-open>
                                            <i class="la la-upload"></i> {{ translate('Upload instead') }}
                                        </button>
                                    </div>

                                    <input type="file" class="d-none" data-selfie-picker accept="image/*">

                                    <div data-selfie-feedback class="fs-12 mt-2"></div>
                                    <canvas data-selfie-canvas class="d-none"></canvas>

                                    {{-- JS puts the captured JPEG here so the existing
                                         multipart submit is unchanged. --}}
                                    <input type="file" class="d-none" data-selfie-file name="{{ $field['name'] }}" accept="image/*">
                                </div>
                            @elseif(($field['type'] ?? 'text') === 'file')
                                <input type="file" class="form-control {{ $field['attrs']['class'] ?? '' }}" name="{{ $field['name'] }}" @if($field['required'] ?? false) required @endif @foreach(($field['attrs'] ?? []) as $attr => $value) @if(!in_array($attr, ['class'])) {{ $attr }}="{{ $value }}" @endif @endforeach>
                            @elseif(($field['name'] ?? '') !== 'email_verify')
                                <input type="{{ $field['type'] ?? 'text' }}" class="form-control {{ $field['attrs']['class'] ?? '' }}" name="{{ $field['name'] }}" value="{{ old($field['name'], $field['name'] === 'country_code' ? '92' : '') }}" @if($field['required'] ?? false) required @endif @foreach(($field['attrs'] ?? []) as $attr => $value) @if(!in_array($attr, ['class'])) {{ $attr }}="{{ $value }}" @endif @endforeach>
                            @endif
                        @endif
                        @if(isset($field['name']))
                            @error($field['name'])<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <datalist id="profession_options"><option value="Software Engineer"><option value="Doctor"><option value="Teacher"><option value="Banker"><option value="Business Owner"><option value="Government Officer"><option value="Armed Forces"><option value="Self-Employed"></datalist>
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary mb-2" data-registration-autofill>{{ translate('Auto Fill Test Data') }}</button>
        <span class="text-muted fs-12 mb-2">{{ translate('Developer helper: fills the form with sample data and jumps to the final step.') }}</span>
    </div>
    <div class="registration-flow-actions d-flex justify-content-between align-items-center border-top pt-4 mt-4 mb-4 pb-2"><button type="button" class="btn registration-gradient-btn" data-registration-prev>{{ translate('Previous') }}</button><button type="button" class="btn registration-gradient-btn ml-auto" data-registration-next>{{ translate('Next') }}</button></div>
</div>

<style>
.registration-flow-window{--step-circle-size:54px;display:flex;align-items:center;justify-content:space-between;padding:26px 28px;margin-bottom:26px;border:1px solid rgba(244,63,94,.14);border-radius:18px;background:linear-gradient(180deg,#fff,#fff7fa);box-shadow:0 18px 45px rgba(244,63,94,.08);overflow:hidden}
.registration-flow-slot{flex:1;display:flex;align-items:center;justify-content:center;text-align:center;position:relative;min-width:0;overflow:visible}.registration-flow-label{display:none}.registration-flow-track{display:flex;align-items:center;justify-content:center;width:100%;position:relative;overflow:visible}.registration-flow-circle{box-sizing:border-box;width:var(--step-circle-size);min-width:var(--step-circle-size);height:var(--step-circle-size);aspect-ratio:1/1;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;position:relative;color:#fff;font-weight:900;font-size:18px;line-height:1;background:linear-gradient(145deg,#ff5c8a,#e11d48);box-shadow:0 16px 32px rgba(225,29,72,.28);border:4px solid #fff;transition:transform .25s ease,box-shadow .25s ease,background .25s ease;z-index:2}.registration-flow-circle:before{content:'';position:absolute;inset:-9px;border-radius:999px;background:rgba(244,63,94,.12);z-index:-1}.registration-flow-line{position:absolute;left:calc(50% + (var(--step-circle-size) / 2));right:calc(-50% + (var(--step-circle-size) / 2));top:50%;height:5px;transform:translateY(-50%);background:linear-gradient(90deg,#f9a8c5,#fb7185);border-radius:999px;box-shadow:inset 0 1px 1px rgba(255,255,255,.7);z-index:1}
.registration-flow-slot.is-active .registration-flow-circle{transform:scale(1.08);background:linear-gradient(145deg,#ff5c8a,#be123c);box-shadow:0 20px 40px rgba(225,29,72,.36)}.registration-flow-slot.is-active .registration-flow-circle:before{background:rgba(244,63,94,.18);inset:-12px}.registration-flow-slot.is-complete .registration-flow-circle{background:linear-gradient(145deg,#fb7185,#e11d48)}.registration-flow-slot.is-complete .registration-flow-circle:after{content:'\2713';font-size:17px;position:absolute}.registration-flow-slot.is-complete .registration-flow-circle{font-size:0}.registration-flow-heading{margin-top:4px}.registration-flow-step{animation:registrationStepIn .24s ease both}.registration-gradient-btn{min-width:130px;color:#fff;border:0;background:linear-gradient(135deg,#ff5c8a,#e11d48);box-shadow:0 10px 24px rgba(225,29,72,.22)}.registration-gradient-btn:hover,.registration-gradient-btn:focus{color:#fff;filter:brightness(.98);transform:translateY(-1px)}.registration-gradient-btn[disabled]{opacity:.5;cursor:not-allowed;transform:none}.registration-flow-actions{gap:16px}.registration-flow .form-label{font-weight:700;color:#4b5563}@keyframes registrationStepIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}@media(max-width:575.98px){.registration-flow-window{--step-circle-size:42px;padding:20px 12px;border-radius:14px}.registration-flow-circle{font-size:15px;border-width:3px}.registration-flow-circle:before{inset:-7px}.registration-flow-line{height:4px}}
.registration-flow,.registration-flow-step,.registration-flow-step .form-group{overflow:visible}.registration-flow{position:relative;z-index:1}.registration-flow-window,.registration-flow-actions{position:relative;z-index:1}.registration-flow-step{position:relative;z-index:5}.registration-flow.is-select-open{z-index:2147483000}.registration-flow .bootstrap-select{position:relative}.registration-flow .bootstrap-select.show,.registration-flow .form-group.is-select-open{position:relative;z-index:2147483600!important}.bootstrap-select .dropdown-menu,.dropdown-menu.show,.bs-container,.bs-container .dropdown-menu{z-index:2147483647!important}.registration-flow .iti{width:100%;display:block}.iti__country-list,.iti--container{z-index:2147483647!important}.hobby-chips-container{gap:8px}.hobby-chip{cursor:pointer;transition:all .2s ease;font-size:14px;background:#fff;border:1px solid #dee2e6;color:#495057}.hobby-chip:hover{border-color:#e11d48;color:#e11d48;transform:translateY(-1px)}.hobby-chip.btn-primary.text-white{background:linear-gradient(135deg,#ff5c8a,#e11d48);border-color:#e11d48;box-shadow:0 4px 12px rgba(225,29,72,.2)}.hobby-chip.btn-primary.text-white:hover{filter:brightness(.95);transform:translateY(-1px)}</style>
<script>
function toggleHobbyChip(btn) {
    var isSelected = btn.getAttribute('data-selected') === 'true';
    if (isSelected) {
        btn.classList.remove('btn-primary', 'text-white');
        btn.classList.add('btn-light', 'border');
        btn.setAttribute('data-selected', 'false');
    } else {
        btn.classList.remove('btn-light', 'border');
        btn.classList.add('btn-primary', 'text-white');
        btn.setAttribute('data-selected', 'true');
    }
    updateHobbyHiddenField();
}
function updateHobbyHiddenField() {
    var selected = [];
    document.querySelectorAll('.hobby-chip[data-selected="true"]').forEach(function (chip) {
        selected.push(chip.textContent.trim());
    });
    var hidden = document.getElementById('hobbies_hidden');
    if (hidden) hidden.value = selected.join(',');
}
</script>
<script>window.registrationStepwiseConfig={csrf:'{{ csrf_token() }}',allCastes:@json($castes->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()),areasByCity:@json($areaOptionsByCity),institutionsByCountry:@json($institutionsByCountry),routes:{states:'{{ route('registration.states.get_by_country') }}',cities:'{{ route('registration.cities.get_by_state') }}',castes:'{{ route('registration.castes.get_by_religion') }}',subCastes:'{{ route('registration.sub_castes.get_by_caste') }}',professions:'{{ route('registration.professions.get_by_category') }}',degrees:'{{ route('registration.degrees.get_by_education_level') }}',fieldsOfStudy:'{{ route('registration.fields_of_study.get_by_degree') }}',institutions:'{{ route('registration.institutions.get_by_location') }}',sectMain:'{{ route('registration.sect_main.get_by_religion') }}',schoolOfThought:'{{ route('registration.school_of_thought.get_by_sect') }}',traditions:'{{ route('registration.traditions.get_by_school_of_thought') }}'},messages:{required:'{{ translate('Please complete the required fields before continuing.') }}',passwordMismatch:'{{ translate('Password confirmation does not match') }}',photoCount:'{{ translate('Please upload 2 to 4 additional photos.') }}',minAdditionalPhotos:'{{ translate('Please upload at least 2 additional photos.') }}',maxAdditionalPhotos:'{{ translate('You can upload at most 4 additional photos.') }}',selfieRequired:'{{ translate('Please capture a live selfie that passes the quality check.') }}',ageRange:'{{ translate('Preferred age from must be less than or equal to preferred age to.') }}',selectStateFirst:'{{ translate('Select state first') }}',selectCountryFirst:'{{ translate('Select country first') }}',loadingStates:'{{ translate('Loading states...') }}',chooseState:'{{ translate('Choose Province / State') }}',unableStates:'{{ translate('Unable to load states') }}',loadingCities:'{{ translate('Loading cities...') }}',chooseCity:'{{ translate('Choose City') }}',unableCities:'{{ translate('Unable to load cities') }}',chooseCaste:'{{ translate('Choose Caste') }}',chooseSubCaste:'{{ translate('Choose Sub Caste') }}',selectCityFirst:'{{ translate('Select city first') }}',chooseArea:'{{ translate('Choose Area') }}',selectCountryForInstitution:'{{ translate('Select country first') }}',chooseInstitution:'{{ translate('Choose College / University') }}',selectProfessionCategory:'{{ translate('Select profession category first') }}',selectEducationLevel:'{{ translate('Select education level first') }}',selectDegree:'{{ translate('Select degree first') }}',selectSectMain:'{{ translate('Select religion first') }}',selectSchoolOfThought:'{{ translate('Select sect first') }}'}};</script>
<script>
    // Translated strings for the selfie capture widget.
    window.registrationSelfieMessages = {
        unsupported: @json(translate('This browser cannot open the camera. Please use a modern browser on a device with a camera.')),
        opening:     @json(translate('Opening camera...')),
        framing:     @json(translate('Face the camera in even light, then press Capture.')),
        denied:      @json(translate('Camera permission was refused. Allow camera access to continue.')),
        nocam:       @json(translate('No camera could be started. Check that another app is not using it.')),
        notready:    @json(translate('The camera is not ready yet. Wait a moment and try again.')),
        lowres:      @json(translate('The photo is too small for verification - it must be at least 480 pixels on the short side.')),
        dark:        @json(translate('Too dark - use brighter, even light and try again.')),
        bright:      @json(translate('Too bright - avoid direct light and try again.')),
        blurry:      @json(translate('Too blurry - use a sharper photo and try again.')),
        readfail:    @json(translate('Could not read the frame.')),
        encodefail:  @json(translate('Could not process the photo. Please capture again.')),
        attachfail:  @json(translate('This browser cannot attach the captured photo. Please update your browser.')),
        accepted:    @json(translate('Selfie accepted.')),
        reading:     @json(translate('Checking the photo...')),
        notimage:    @json(translate('That file is not an image. Choose a JPG or PNG photo.')),
        toobig:      @json(translate('That photo is too large. Choose one under 10 MB.')),
        loadfail:    @json(translate('Could not read that photo. Try another one.')),
        capturedLabel: @json(translate('captured')),
        uploadedLabel: @json(translate('uploaded'))
    };
</script>
<script src="{{ static_asset('assets/js/registration-selfie-camera.js') }}"></script>
<script src="{{ static_asset('assets/js/registration-web-stepwise.js') }}"></script>
<script>
(function () {
    var btn = document.querySelector('[data-registration-autofill]');
    if (!btn) return;

    function setField(name, value) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function selectFirstOption(name, fallback) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) return;
        var opts = Array.from(el.options || []);
        var opt = opts.find(function (o) { return o.value; }) || opts[1];
        el.value = opt ? opt.value : fallback;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    btn.addEventListener('click', function () {
        setField('first_name', 'JZ');
        setField('last_name', 'JZ');
        setField('weight', '60');
        setField('complexion', 'Not Specified');
        setField('living_with', 'Family');
        setField('smoke', 'No');
        setField('drink', 'No');
        setField('personal_value', 'Family Oriented');
        setField('company', 'Not Specified');
        setField('future_goals', 'Build a peaceful and stable married life.');
        setField('children', '0');
        setField('partner_lifestyle', 'Compatible');
        setField('partner_prayer', 'Flexible');
        setField('full_name', 'JZ');
        setField('date_of_birth', '1996-07-11');
        setField('dob_day', '11');
        setField('dob_month', '07');
        setField('dob_year', '1996');
        setField('area', 'Saddar');
        setField('country_code', '92');
        setField('phone', '33387678789');
        setField('email', 'test' + Date.now().toString().slice(-6) + '@example.com');
        setField('graduation_year', '2024');
        setField('education_status', 'completed');
        setField('expected_graduation_year', '');
        setField('height', '6');
        setField('diet', 'Vegetarian');
        setField('employment_status', 'government');
        setField('job_title', 'kklkl');
        setField('organization', 'mkllmk');
        setField('years_of_experience', '3');
        setField('about_me', 'klmklmlkmk');
        setField('cnic_number', '3730240741611');
        setField('hobbies', 'Reading,Movies,Writing');
        setField('father_occupation', 'mklkl');
        setField('mother_occupation', 'mlmkkl');
        setField('siblings_sisters', '1');
        setField('siblings_brothers', '2');
        setField('family_location', 'mklklmkl');
        setField('live_with_family', 'yes');
        setField('family_state', 'mklmkl');
        setField('family_city', 'mklmk');
        setField('partner_age_min', '20');
        setField('partner_age_max', '30');
        setField('partner_height_min', '5');
        setField('partner_height_max', '6');
        setField('partner_education', 'mklkl');
        setField('partner_profession', 'mklm');
        setField('partner_income_min', '999');
        setField('partner_income_max', '99999');
        setField('deal_breakers[]', 'jkljkljkljklklkl');
        setField('password', '123456789');
        setField('password_confirmation', '123456789');
        setField('email_verify', '33384@iqraisb.edu.pk');

        ['on_behalf','gender','marriage_timeline','willing_to_work_after_marriage','expects_spouse_to_work','religion_id','mother_tongue','sect_main_id','school_of_thought_id','tradition_id','country_id','state_id','city_id','caste_id','sub_caste_id','marital_status_id','education_level_id','degree_id','field_of_study_id','institution_id','annual_salary_range_id','profession_category_id','profession_id','partner_marital_status_id','partner_religion_id','partner_caste_id','partner_language_id','partner_country_id','partner_state_id','partner_city_id'].forEach(function (name) {
            selectFirstOption(name);
        });

        var step = document.querySelector('.registration-flow-step[data-step="18"]');
        if (step) {
            step.classList.add('is-autofilled');
        }
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    });
})();
</script>