@extends('frontend.layouts.member_panel')
@section('panel_content')
    @php
        $user = Auth::user();
        $col  = 4;
        $profile_picture_privacy = get_setting('profile_picture_privacy');
        $gallery_image_privacy = get_setting('gallery_image_privacy');
        $recommendedPackage = \App\Support\RegistrationReward::nextRecommendedPackage($user->member?->package);
        if($profile_picture_privacy == 'only_me'){
            $col++;
        }
        elseif($gallery_image_privacy == 'only_me') {
            $col++;
        }
    @endphp
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between">
        <div class="mr-3">
            <strong>{{ translate('AI matrimonial dashboard is available.') }}</strong>
            <span>{{ translate('Check compatibility scores, verification status, proposals, safety, and AI activity.') }}</span>
        </div>
        <a href="{{ route('member.v1_dashboard') }}" class="btn btn-sm btn-primary mt-2 mt-md-0">{{ translate('Open AI Dashboard') }}</a>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- AI identity verification status.                                    --}}
    {{-- Shown only while the member is not AI-verified. Registration never  --}}
    {{-- blocks on the model, so this is the recovery path: model was down,   --}}
    {{-- no usable photo on file, or the result came back MANUAL_REVIEW.      --}}
    {{-- ------------------------------------------------------------------ --}}
    @php
        $aiStatus = $user->member?->ai_verification_status ?? 'not_started';
        $aiRecommendation = $user->member?->ai_verification_recommendation;
        $aiAttempts = (int) ($user->member?->ai_verification_attempts ?? 0);
        $aiVerified = $aiStatus === 'approved';
        $aiMeta = match ($aiStatus) {
            'approved'      => ['alert-success', 'la-check-circle',        translate('Your identity is verified by our AI check.')],
            'rejected'      => ['alert-danger',  'la-times-circle',        translate('The automated identity check did not pass. Our team will review your documents.')],
            'manual_review' => ['alert-warning', 'la-user-check',          translate('Your verification needs a manual review. Adding your CNIC and a selfie speeds this up.')],
            'failed'        => ['alert-warning', 'la-exclamation-triangle',translate('The verification service could not be reached. Please try again.')],
            'pending'       => ['alert-info',    'la-spinner',             translate('Identity verification is running in the background.')],
            default         => ['alert-secondary','la-id-card',            translate('Your identity is not verified yet.')],
        };
    @endphp

    @if(!$aiVerified)
        <div class="alert {{ $aiMeta[0] }} d-flex flex-wrap align-items-center justify-content-between">
            <div class="mr-3">
                <strong>
                    <i class="la {{ $aiMeta[1] }}"></i>
                    {{ translate('AI Identity Verification') }}
                </strong>
                <span class="d-block fs-13">{{ $aiMeta[2] }}</span>
                @if($aiAttempts > 0)
                    <span class="d-block fs-12 opacity-70">
                        {{ translate('Attempts') }}: {{ $aiAttempts }}
                        @if($aiRecommendation) &middot; {{ translate('Last result') }}: {{ $aiRecommendation }} @endif
                        @if($user->member?->ai_verification_last_attempt_at)
                            &middot; {{ $user->member->ai_verification_last_attempt_at->diffForHumans() }}
                        @endif
                    </span>
                @endif
            </div>
            <div class="mt-2 mt-md-0 d-flex align-items-center">
                @if(in_array($aiStatus, ['not_started', 'manual_review']))
                    <a href="{{ route('member.verification') }}" class="btn btn-sm btn-outline-primary mr-2">
                        {{ translate('Upload CNIC & Selfie') }}
                    </a>
                @else
                    <span class="badge badge-inline badge-warning mr-2">{{ translate('Verification in progress') }}</span>
                @endif
                <form action="{{ route('member.ai_verification.run') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="la la-shield"></i>
                        {{ $aiAttempts > 0 ? translate('Retry Verification') : translate('Verify My Identity') }}
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="row gutters-5 mb-4">
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Current Coin Balance') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ (int) ($user->member?->remaining_interest ?? 0) }}</div>
                <div class="fs-12 opacity-70">{{ translate('Coins received from the active package') }}</div>
                <a href="{{ route('package_usage_history') }}" class="btn btn-link p-0 mt-2 fs-12">{{ translate('View usage history') }}</a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Profile View Balance') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ (int) ($user->member?->remaining_profile_viewer_view ?? 0) }}</div>
                <div class="fs-12 opacity-70">{{ translate('Remaining profile detail views') }}</div>
                <a href="{{ route('package_usage_history') }}" class="btn btn-link p-0 mt-2 fs-12">{{ translate('See profile-view usage') }}</a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Current Package') }}</div>
                <div class="h5 fw-600 mb-1">{{ $user->member?->package?->name ?? translate('No active package') }}</div>
                <div class="fs-12 opacity-70">
                    {{ translate('Package expiry') }}:
                    @if($user->member?->package_validity && package_validity($user->id))
                        {{ $user->member->package_validity }}
                    @else
                        <span class="text-danger">{{ translate('Expired') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Package Entitlements') }}</div>
                <div class="fs-12 mb-1">{{ translate('Interest') }}: {{ (int) ($user->member?->remaining_interest ?? 0) }}</div>
                <div class="fs-12 mb-1">{{ translate('Contact Views') }}: {{ (int) ($user->member?->remaining_contact_view ?? 0) }}</div>
                <div class="fs-12">{{ translate('Gallery Uploads') }}: {{ (int) ($user->member?->remaining_photo_gallery ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="row gutters-5 row-cols-xl-{{ $col }} row-cols-2">
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="la la-heart-o la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_interest') }}</div>
                <div class="opacity-50">{{ translate('Coin') }} <br> {{ translate('Balance') }}</div>
            </div>
        </div>
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="las la-phone la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_contact_view') }}</div>
                <div class="opacity-50 ">{{ translate('Remaining') }} <br> {{ translate('Contact View') }}</div>
            </div>
        </div>
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="las la-phone la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_profile_viewer_view') }}</div>
                <div class="opacity-50 ">{{ translate('Remaining') }} <br> {{ translate('Profile Viewer View') }}</div>
            </div>
        </div>
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="las la-image la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-center text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_photo_gallery') }}</div>
                <div class="opacity-50 text-center">{{ translate('Remaining') }} <br> {{ translate('Gallery Image Upload') }}</div>
            </div>
        </div>
        @if($profile_picture_privacy == 'only_me')
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="las la-user-circle la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_profile_image_view') }}</div>
                <div class="opacity-50 ">{{ translate('Remaining') }} <br> {{ translate('Profile Picture View') }}</div>
            </div>
        </div>
        @endif
        @if($gallery_image_privacy == 'only_me')
        <div class="col mx-auto mb-3" >
            <div class="bg-light rounded overflow-hidden text-center p-3">
                <i class="las la-images la-2x mb-3 text-primary-grad"></i>
                <div class="h4 fw-700 text-center text-primary-grad">{{ get_remaining_package_value($user->id,'remaining_gallery_image_view') }}</div>
                <div class="opacity-50 text-center">{{ translate('Remaining') }} <br> {{ translate('Gallery Images View') }}</div>
            </div>
        </div>
        @endif
    </div>

    <div class="row gutters-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="fs-16 mb-0">{{  translate('Current package') }}</h2>
                </div>
                @if ($user->member->current_package_id != null)
                    <div class="card-body">
                        <div class="text-center mb-4 mt-3">
                            <img class="mw-100 mx-auto mb-4" src="{{ uploaded_asset($user->member->package->image) }}" height="130">
                            <h5 class="mb-3 h5 fw-600">{{$user->member->package->name}}</h5>
                        </div>
                        <ul class="list-group list-group-raw fs-15 mb-4 pb-4 border-bottom">
                            <li class="list-group-item py-2">
                                <i class="las la-check text-success mr-2"></i>
                                {{ $user->member->package->express_interest }} {{ translate('Coins') }}
                            </li>
                            <li class="list-group-item py-2">
                                <i class="las la-check text-success mr-2"></i>
                                {{ $user->member->package->photo_gallery }} {{ translate('Gallery Photo Upload') }}
                            </li>
                            <li class="list-group-item py-2">
                                <i class="las la-check text-success mr-2"></i>
                                {{ $user->member->package->contact }} {{ translate('Contact Info View') }}
                            </li>
                            <li class="list-group-item py-2">
                                <i class="las la-check text-success mr-2"></i>
                                {{ $user->member->package->profile_viewers_view }} {{ translate('Profile Viewer View') }}
                            </li>
                            @if($profile_picture_privacy == 'only_me')
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $user->member->package->profile_image_view }} {{ translate('Profile Image View') }}
                                </li>
                            @endif
                            @if($gallery_image_privacy == 'only_me')
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $user->member->package->gallery_image_view }} {{ translate('Gallery Image View') }}
                                </li>
                            @endif
                            <li class="list-group-item py-2 text-line-through">
                                @if( $user->member->package->auto_profile_match == 0 )
                                    <i class="las la-times text-danger mr-2"></i>
                                    <del class="opacity-60">{{ translate('Show Auto Profile Match') }}</del>
                                @else
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ translate('Show Auto Profile Match') }}
                                @endif
                            </li>
                            <li class="list-group-item py-2 text-line-through">
                                @if( $user->member->package->auto_horoscope_profile_match == 0 )
                                    <i class="las la-times text-danger mr-2"></i>
                                    <del class="opacity-60">{{ translate('Show Auto Horoscope Profile Match') }}</del>
                                @else
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ translate('Show Auto Horoscope Profile Match') }}
                                @endif
                            </li>
                        </ul>
                        <h4 class="fs-18 mb-3">
                        {{ translate('Package expiry date') }}:
                        @if(package_validity($user->id))
                            {{ $user->member->package_validity }}
                        @else
                            <span class="text-danger">{{translate('Expired')}}</span>
                        @endif
                        </h4>
                        <a href="{{ route('packages') }}" class="btn btn-success d-inline-block">{{ translate('Upgrade Package') }}</a>

                        @if($recommendedPackage)
                            <div class="alert alert-primary mt-4 mb-0 text-left">
                                <div class="fw-700 mb-1">{{ translate('Recommended Next Package') }}: {{ $recommendedPackage->name }}</div>
                                <div class="fs-13 opacity-80 mb-2">
                                    {{ translate('Unlock more coins and premium discovery features with this package.') }}
                                </div>
                                <a href="{{ route('package_payment_methods', encrypt($recommendedPackage->id)) }}" class="btn btn-sm btn-primary">
                                    {{ translate('View Recommended Package') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="card mb-0 p-5 h-20 d-flex align-items-center justify-content-center">
                            {{ translate('No Package Available') }} 
                            <div class="mt-2">
                                <a href="{{ route('packages') }}"
                                    class="btn btn-sm btn-primary">{{ translate('Purchase a Package') }}</a>
                            </div>    
                    </div>   
                @endif    
            </div>
        </div>
        <div class="col-md-6">
            @if(get_setting('member_verification') && $user->approved == 0)
                <div class="card mb-0 p-5 h-15 d-flex align-items-center justify-content-center mb-2">
                    @if (in_array($user->member?->ai_verification_status ?? 'not_started', ['pending', 'submitted', 'under_review', 'processing']))
                        <div class="my-n4 py-1 text-center">
                            <img src="{{ static_asset('assets/img/non_verified.png') }}" alt=""
                                class="w-xxl-130px w-90px d-block">
                            <span class="badge badge-inline badge-warning">{{ translate('Verification in progress') }}</span>
                        </div>
                    @elseif ($user->approved == 0)
                        <div class="my-n4 py-1 text-center">
                            <img src="{{ static_asset('assets/img/non_verified.png') }}" alt=""
                                class="w-xxl-130px w-90px d-block">
                            <a href="{{ route('member.verification') }}"
                                class="btn btn-sm btn-primary">{{ translate('Verify Now') }}</a>
                        </div>
                    @else
                        <div class="my-2 py-1">
                            <img src="{{ static_asset('assets/img/verified.png') }}" alt="" width="">
                        </div>
                    @endif
                </div>
            @endif
            
            @if (Auth::user()->member->current_package_id != null)
                <div class="card">
                    @php
                        $canRefresh = true;
                        if(Auth::user()->match_refresh_updated_at){
                            $nextRefresh = \Carbon\Carbon::parse(Auth::user()->match_refresh_updated_at)->addMinutes(30);
                            if($nextRefresh->isFuture()){
                                $canRefresh = false;
                            }
                        }
                    @endphp
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div class="d-flex align-items-center">

                            <h2 class="fs-16 mb-0 mr-2">
                                {{ translate('Matched profile') }}
                            </h2>

                            @if (Auth::user()->member->auto_profile_match == 1)
                                {{-- Refresh Button --}}
                                @if($canRefresh)
                                    <a href="{{ route('match.refresh') }}"
                                    class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                    title="{{ translate('Refresh') }}">
                                        <i class="las la-sync"></i>
                                    </a>
                                @else
                                    <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm"
                                            disabled
                                            title="{{ translate('You can refresh after 30 minutes') }}">
                                        <i class="las la-sync"></i>
                                    </button>
                                @endif
                            @endif

                        </div>

                        {{-- Arrow Icon --}}
                        <a href="{{ route('my_matched_profiles') }}" class="text-dark">
                            <i class="las la-arrow-right fs-18"></i>
                        </a>

                    </div>
                    <div class="card-body">
                        @if(Auth::user()->member->auto_profile_match == 1)
                        <div class="scrolling-horoscope-partner-match">
                            @forelse ($similar_profiles->shuffle() as $similar_profile)
                            @if($similar_profile->user != null)
                                <a href="{{ route('member_profile', $similar_profile->match_id) }}" class="text-reset border rounded row no-gutters align-items-center mb-3">
                                    <div class="col-auto w-100px">
                                    @php
                                        $avatar_image = $similar_profile->user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                        $profile_picture_show = show_profile_picture($similar_profile->user);
                                    @endphp
                                    <img
                                        @if ($profile_picture_show)
                                        src="{{ uploaded_asset($similar_profile->user->photo) }}"
                                        @else
                                        src="{{ static_asset($avatar_image) }}"
                                        @endif
                                        onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
                                        class="img-fit w-100 size-100px"
                                    >
                                    </div>
                                    <div class="col">
                                    <div class="p-3">
                                        <h5 class="fs-16 text-body text-truncate">{{ $similar_profile->user->first_name.' '.$similar_profile->user->last_name }}</h5>
                                        <div class="fs-12 text-truncate-3">
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_profile->user->member->birthday))
                                                {{ \Carbon\Carbon::parse($similar_profile->user->member->birthday)->age }} {{ translate('yrs') }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_profile->user->physical_attributes->height))
                                                {{ $similar_profile->user->physical_attributes->height }} {{ translate('Feet') }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_profile->user->member->marital_status->name))
                                                {{ $similar_profile->user->member->marital_status->name }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                {{ !empty($similar_profile->user->spiritual_backgrounds->religion->name) ? $similar_profile->user->spiritual_backgrounds->religion->name.', ' : "" }}
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                {{ !empty($similar_profile->user->spiritual_backgrounds->caste->name) ? $similar_profile->user->spiritual_backgrounds->caste->name.', ' : "" }}
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                <td class="py-1">{{ !empty($similar_profile->user->spiritual_backgrounds->sub_caste->name) ? $similar_profile->user->spiritual_backgrounds->sub_caste->name : "" }}</td>
                                            </span>
                                        </div>
                                    </div>
                                    </div>
                                </a>
                            @endif
                            @empty
                                @php
                                    $expectation = Auth::user()->partner_expectations;

                                    $expectation_filled = $expectation &&
                                                        !empty($expectation->residence_country_id) &&
                                                        !empty($expectation->weight) &&
                                                        !empty($expectation->marital_status_id) &&
                                                        !empty($expectation->religion) &&
                                                        !empty($expectation->language_id) &&
                                                        !empty($expectation->education) &&
                                                        !empty($expectation->profession) &&
                                                        !empty($expectation->smoking_acceptable) &&
                                                        !empty($expectation->drinking_acceptable) &&
                                                        !empty($expectation->diet) &&
                                                        !empty($expectation->preferred_state_id) &&
                                                        !empty($expectation->preferred_country_id) &&
                                                        !empty($expectation->family_value_id) &&
                                                        !empty($expectation->height);
                                @endphp

                                @if(!$expectation_filled)
                                    <div class="alert alert-info">
                                        {{ translate('Update your partner expectation for auto match making') }}
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        {{ translate('No matched profile found') }}
                                    </div>
                                @endif
                            @endforelse
                        </div>
                        @else
                            <div class="alert alert-info">{{  translate('Upgrade your package for auto match making') }}</div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    @php
                        $canHoroscopeRefresh = true;
                        if(Auth::user()->refresh_updated_at){
                            $nextRefresh = \Carbon\Carbon::parse(Auth::user()->refresh_updated_at)->addMinutes(30);
                            if($nextRefresh->isFuture()){
                                $canHoroscopeRefresh = false;
                            }
                        }
                    @endphp
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div class="d-flex align-items-center">

                            <h2 class="fs-16 mb-0 mr-2">
                                {{ translate('Horoscope Matched profile') }}
                            </h2>

                            @if (Auth::user()->member->auto_horoscope_profile_match == 1)
                                {{-- Refresh Button --}}
                                @if($canHoroscopeRefresh)
                                    <a href="{{ route('horoscope.match.refresh') }}"
                                    class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                    title="{{ translate('Refresh') }}">
                                        <i class="las la-sync"></i>
                                    </a>
                                @else
                                    <button class="btn btn-soft-secondary btn-circle btn-icon btn-sm"
                                            disabled
                                            title="{{ translate('You can refresh after 30 minutes') }}">
                                        <i class="las la-sync"></i>
                                    </button>
                                @endif
                            @endif

                        </div>

                        {{-- Arrow Icon --}}
                        <a href="{{ route('horoscope_matched_profiles') }}" class="text-dark">
                            <i class="las la-arrow-right fs-18"></i>
                        </a>

                    </div>
                    <div class="card-body">
                        @if(Auth::user()->member->auto_horoscope_profile_match == 1)
                        <div class="scrolling-horoscope-partner-match">
                            @forelse ($similar_horoscope_profiles->shuffle() as $similar_horoscope_profile)
                            @if($similar_horoscope_profile->user != null)
                                <a href="{{ route('member_profile', $similar_horoscope_profile->match_id) }}" class="text-reset border rounded row no-gutters align-items-center mb-3">
                                    <div class="col-auto w-100px">
                                    @php
                                        $avatar_image = $similar_horoscope_profile->user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                        $profile_picture_show = show_profile_picture($similar_horoscope_profile->user);
                                    @endphp
                                    <img
                                        @if ($profile_picture_show)
                                        src="{{ uploaded_asset($similar_horoscope_profile->user->photo) }}"
                                        @else
                                        src="{{ static_asset($avatar_image) }}"
                                        @endif
                                        onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
                                        class="img-fit w-100 size-100px"
                                    >
                                    </div>
                                    <div class="col">
                                    <div class="p-3">
                                        <h5 class="fs-16 text-body text-truncate">{{ $similar_horoscope_profile->user->first_name.' '.$similar_horoscope_profile->user->last_name }}</h5>
                                        <div class="fs-12 text-truncate-3">
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_horoscope_profile->user->member->birthday))
                                                {{ \Carbon\Carbon::parse($similar_horoscope_profile->user->member->birthday)->age }} {{ translate('yrs') }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_horoscope_profile->user->physical_attributes->height))
                                                {{ $similar_horoscope_profile->user->physical_attributes->height }} {{ translate('Feet') }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                @if(!empty($similar_horoscope_profile->user->member->marital_status->name))
                                                {{ $similar_horoscope_profile->user->member->marital_status->name }},
                                                @endif
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                {{ !empty($similar_horoscope_profile->user->spiritual_backgrounds->religion->name) ? $similar_horoscope_profile->user->spiritual_backgrounds->religion->name.', ' : "" }}
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                {{ !empty($similar_horoscope_profile->user->spiritual_backgrounds->caste->name) ? $similar_horoscope_profile->user->spiritual_backgrounds->caste->name.', ' : "" }}
                                            </span>
                                            <span class="mr-1 d-inline-block">
                                                <td class="py-1">{{ !empty($similar_horoscope_profile->user->spiritual_backgrounds->sub_caste->name) ? $similar_horoscope_profile->user->spiritual_backgrounds->sub_caste->name : "" }}</td>
                                            </span>
                                        </div>
                                    </div>
                                    </div>
                                </a>
                            @endif
                            @empty
                                @php
                                    $astrologies = Auth::user()->astrologies;
                                    $horoscope_filled = !empty($astrologies->moon_sign) 
                                                        && !empty($astrologies->nadi) 
                                                        && !empty($astrologies->manglik) 
                                                        && !empty($astrologies->gana) 
                                                        && !empty($astrologies->time_of_birth);
                                @endphp
                                @if(!$horoscope_filled)
                                    <div class="alert alert-info">
                                        {{ translate('Update your astronomic & horoscope info for auto horoscope match making') }}
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        {{ translate('No matched horoscope profile found') }}
                                    </div>
                                @endif
                            @endforelse
                        </div>
                        @else
                            <div class="alert alert-info">{{  translate('Upgrade your package for auto horoscope match making') }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>


@endsection




