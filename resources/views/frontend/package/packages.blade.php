@extends('frontend.layouts.app')
@section('content')
<section class="pt-6 pb-4 bg-white text-center">
    <div class="container">
        <h1 class="mb-0 fw-600 text-dark">{{ translate('Select Your Package') }}</h1>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="alert alert-primary mb-4 text-left">
            <div class="fw-700 mb-1">{{ translate('Hamqadam Packages') }}</div>
            <div class="fs-13">
                {{ translate('The Basic Free package is applied automatically after registration.') }}
            </div>
        </div>


        <div class="aiz-carousel" data-items="4" data-xl-items="3" data-md-items="2" data-sm-items="1" data-dots='true' data-infinite='true' data-autoplay='true'>
            @foreach ($packages as $key => $package)
                @php
                    $latestPaidPayment = null;
                    $packageExpiryDate = null;
                    $isPackageActive = false;
                    $isCurrentPackage = false;

                    if (Auth::check()) {
                        $latestPaidPayment = Auth::user()->payckage_payments()
                            ->where('package_id', $package->id)
                            ->where('payment_status', 'Paid')
                            ->latest('id')
                            ->first();

                        if ($latestPaidPayment) {
                            $packageExpiryDate = $latestPaidPayment->subscription_ends_at
                                ? \Carbon\Carbon::parse($latestPaidPayment->subscription_ends_at)
                                : \Carbon\Carbon::parse($latestPaidPayment->created_at)->addDays((int) $package->validity);

                            $isPackageActive = $packageExpiryDate->copy()->endOfDay()->gte(now());
                        }

                        $isCurrentPackage = $isPackageActive && ((int) (Auth::user()->member?->current_package_id ?? 0) === (int) $package->id);
                    }

                    $recommendedPackage = Auth::check() ? \App\Support\RegistrationReward::nextRecommendedPackage(Auth::user()->member?->package) : null;
                    $isRecommended = $recommendedPackage && $recommendedPackage->id == $package->id;
                @endphp
                <div class="carousel-box">
                    <div class="overflow-hidden shadow-none border-right position-relative">
                        @if($isCurrentPackage)
                            <span class="badge badge-inline badge-success absolute-top-left m-2">{{ translate('Current') }}</span>
                        @elseif($isRecommended)
                            <span class="badge badge-inline badge-primary absolute-top-left m-2">{{ translate('Recommended') }}</span>
                        @endif

                        <div class="card-body">
                            <div class="text-center mb-4 mt-3">
                                <img class="mw-100 mx-auto mb-4" src="{{ uploaded_asset($package->image) }}" height="130">
                                <h5 class="mb-3 h5 fw-600">{{ $package->name }}</h5>


                            </div>

                            <ul class="list-group list-group-raw fs-15 mb-5">
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->express_interest }} {{ translate('Coins') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->photo_gallery }} {{ translate('Gallery Photo Upload') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->contact }} {{ translate('Contact Info View') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->profile_viewers_view }} {{ translate('Profile Viewer View') }}
                                </li>
                                @if(get_setting('profile_picture_privacy') == 'only_me')
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->profile_image_view }} {{ translate('Profile Image View') }}
                                </li>
                                @endif
                                @if(get_setting('gallery_image_privacy') == 'only_me')
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->gallery_image_view }} {{ translate('Gallery Image View') }}
                                </li>
                                @endif
                                <li class="list-group-item py-2 text-line-through">
                                    @if($package->auto_profile_match == 0)
                                        <i class="las la-times text-danger mr-2"></i>
                                        <del class="opacity-60">{{ translate('Show Auto Profile Match') }}</del>
                                    @else
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ translate('Show Auto Profile Match') }}
                                    @endif
                                </li>
                                <li class="list-group-item py-2 text-line-through">
                                    @if($package->auto_horoscope_profile_match == 0)
                                        <i class="las la-times text-danger mr-2"></i>
                                        <del class="opacity-60">{{ translate('Show Auto Horoscope Profile Match') }}</del>
                                    @else
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ translate('Show Auto Horoscope Profile Match') }}
                                    @endif
                                </li>
                            </ul>

                            <div class="mb-5 text-dark text-center">
                                @if ($package->id == 1)
                                    <span class="display-4 fw-600 lh-1 mb-0">{{ translate('Free') }}</span>
                                @else
                                    <span class="display-4 fw-600 lh-1 mb-0">{{ single_price($package->price) }}</span>
                                @endif
                                <span class="text-secondary d-block">{{ $package->validity }} {{ translate('Days') }}</span>
                            </div>

                            <div class="text-center">
                                @if ($package->id != 1)
                                    @if(Auth::check() && $isPackageActive)
                                        <button type="button" class="btn btn-soft-success" disabled>{{ translate('Activated') }}</button>
                                    @elseif(Auth::check())
                                        <a href="{{ route('package_payment_methods', encrypt($package->id)) }}" class="btn btn-primary">{{ translate('Purchase This Package') }}</a>
                                    @else
                                        <button type="button" onclick="loginModal()" class="btn btn-primary">{{ translate('Purchase This Package') }}</button>
                                    @endif
                                @elseif($package->id == 1 && Auth::check())
                                    <a href="javascript:void(0);" class="btn btn-soft-success">
                                        {{ $isPackageActive ? translate('Basic Free Package Active') : translate('Basic Free Package Auto Activated') }}
                                    </a>
                                @elseif($package->id == 1)
                                    <button type="button" onclick="loginModal()" class="btn btn-primary">
                                        {{ translate('Register to Get This Free Package') }}
                                    </button>
                                @else
                                    <a href="javascript:void(0);" class="btn btn-light"><del>{{ translate('Activate This Free Package') }}</del></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('modal')
    @include('modals.login_modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function loginModal(){
        $('#LoginModal').modal();
    }

    function package_update_alert(){
      $('.package_update_alert_modal').modal('show');
    }
</script>
@endsection