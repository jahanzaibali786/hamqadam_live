@extends('frontend.layouts.app')
@section('content')
@php
    $registrationPackage = \App\Support\RegistrationReward::registrationPackage();
@endphp
<section class="hq-reference-page-hero hq-package-reference-hero text-center">
    <div class="container">
        <span class="hq-reference-eyebrow"><i class="las la-shield-alt"></i> {{ translate('Transparent & Auspicious Membership') }}</span>
        <h1>{{ translate('Choose the Perfect Plan to Find Your Life Partner') }}</h1>
        <p>{{ translate('Every package includes verified profiles, protected communication, and thoughtful matrimonial support for your family.') }}</p>
        <div class="hq-hero-chip-row justify-content-center">
            <span><i class="las la-check-circle"></i> {{ translate('100% CNIC Verified') }}</span>
            <span><i class="las la-lock"></i> {{ translate('Bank-Grade Privacy') }}</span>
            <span><i class="las la-coins"></i> {{ translate('Instant Coin Top Up') }}</span>
            <span><i class="las la-headset"></i> {{ translate('Family Concierge') }}</span>
        </div>
    </div>
</section>

<section class="hq-package-page hq-reference-package-page py-5">
    <div class="container">
        <div class="hq-reference-notice mb-4 text-left">
            <div class="fw-700 mb-1">{{ translate('Hamqadam Packages') }}</div>
            <div class="fs-13">
                {{ translate('The') }} {{ $registrationPackage?->name ?? translate('registration package') }} {{ translate('package is applied automatically after registration.') }}
            </div>
        </div>


        <div class="hq-plan-grid">
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

                        $isCurrentPackage = $isPackageActive
                            && ((int) (Auth::user()->member?->current_package_id ?? 0) === (int) $package->id);
                    }

                    $recommendedPackage = Auth::check()
                        ? \App\Support\RegistrationReward::nextRecommendedPackage(Auth::user()->member?->package)
                        : null;
                    $isRecommended = ($recommendedPackage && $recommendedPackage->id == $package->id) || $loop->iteration === 3;
                    $featureFlags = (array) ($package->feature_flags ?? []);
                    $tierLabels = [
                        translate('Discovery Tier'),
                        translate('Active Search'),
                        translate('Recommended Suite'),
                        translate('VIP Privilege'),
                    ];
                    $tierLabel = $tierLabels[$loop->index] ?? translate('Matrimonial Plan');
                @endphp

                <article class="hq-design-plan-card {{ $isRecommended ? 'is-recommended' : '' }} {{ $isCurrentPackage ? 'is-current' : '' }}">
                    @if ($isRecommended)
                        <div class="hq-plan-ribbon"><i class="las la-star"></i> {{ translate('Most Auspicious & Popular') }}</div>
                    @endif

                    <div class="hq-plan-topline">
                        <span>{{ $tierLabel }}</span>
                        @if($isCurrentPackage)
                            <b>{{ translate('Current') }}</b>
                        @elseif($isRecommended)
                            <b>{{ translate('Popular') }}</b>
                        @elseif($loop->first)
                            <b>{{ translate('Base') }}</b>
                        @endif
                    </div>

                    <h3>{{ $package->name }}</h3>
                    <p class="hq-plan-intro">
                        @if($loop->first)
                            {{ translate('Essential portal access for serious candidates and guardians.') }}
                        @elseif($loop->iteration === 2)
                            {{ translate('Enhanced outreach to establish respectful initial proposals.') }}
                        @elseif($loop->iteration === 3)
                            {{ translate('Complete matrimonial tools including direct verified family contact.') }}
                        @else
                            {{ translate('Unrestricted elite matchmaking for families and executives.') }}
                        @endif
                    </p>

                    <div class="hq-plan-price-box">
                        <div>
                            @if ((float) $package->price <= 0)
                                <strong>0.00</strong>
                            @else
                                <strong>{{ number_format((float) $package->price, 2) }}</strong>
                            @endif
                            <span>{{ strtoupper((string) (\App\Models\Currency::find(get_setting('system_default_currency'))?->code ?? 'PKR')) }}</span>
                        </div>
                        <small><i class="las la-clock"></i> {{ (int) $package->validity }} {{ translate('Days Validity') }}</small>
                    </div>

                    <ul class="hq-plan-features">
                        <li><i class="las la-check-circle"></i><strong>{{ (int) $package->express_interest }}</strong> {{ translate('Coins Included') }}</li>
                        <li><i class="las la-check-circle"></i><strong>{{ (int) $package->photo_gallery }}</strong> {{ translate('Gallery Photo Uploads') }}</li>
                        <li class="{{ (int)$package->contact < 1 ? 'is-muted' : '' }}"><i class="las {{ (int)$package->contact > 0 ? 'la-check-circle' : 'la-lock' }}"></i><strong>{{ (int) $package->contact }}</strong> {{ translate('Direct Contact Info Views') }}</li>
                        <li class="{{ !$package->auto_profile_match ? 'is-muted' : '' }}"><i class="las {{ $package->auto_profile_match ? 'la-check-circle' : 'la-times-circle' }}"></i>{{ translate('Auto Profile Match Included') }}</li>
                        <li class="{{ !$package->auto_horoscope_profile_match ? 'is-muted' : '' }}"><i class="las {{ $package->auto_horoscope_profile_match ? 'la-check-circle' : 'la-times-circle' }}"></i>{{ translate('Auto Horoscope Matching') }}</li>
                        @if(!empty($featureFlags['advanced_filters']))
                            <li><i class="las la-check-circle"></i>{{ translate('Advanced Filters') }}</li>
                        @endif
                        @if(!empty($featureFlags['priority_search']) || !empty($featureFlags['boost_profile']))
                            <li><i class="las la-star"></i>{{ translate('Priority Search & Profile Boost') }}</li>
                        @endif
                        @if(!empty($featureFlags['unlimited_messaging']))
                            <li><i class="las la-comment-dots"></i>{{ translate('Unlimited Messaging') }}</li>
                        @endif
                    </ul>

                    <div class="hq-plan-action">
                        @if(Auth::check() && $isCurrentPackage)
                            <button type="button" class="btn btn-soft-success btn-block" disabled>{{ translate('Current Plan') }}</button>
                        @elseif ($package->id != 1 && Auth::check() && $isPackageActive)
                            <button type="button" class="btn btn-soft-success btn-block" disabled>{{ translate('Activated') }}</button>
                        @elseif ($package->id != 1 && Auth::check())
                            <a href="{{ route('package_payment_methods', encrypt($package->id)) }}" class="btn btn-primary btn-block">{{ translate('Purchase') }} {{ $package->name }}</a>
                        @elseif ($package->id != 1)
                            <button type="button" onclick="loginModal()" class="btn btn-primary btn-block">{{ translate('Purchase') }} {{ $package->name }}</button>
                        @elseif(Auth::check())
                            <button type="button" class="btn btn-soft-primary btn-block" disabled>{{ translate('Current Plan / Get Started') }}</button>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-soft-primary btn-block">{{ translate('Current Plan / Get Started') }}</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="hq-design-section hq-design-section-alt">
    <div class="container">
        <div class="hq-coins-panel">
            <div>
                <span class="hq-reference-eyebrow"><i class="las la-wallet"></i> {{ translate('Hamqadam Wallet Ecosystem') }}</span>
                <h2>{{ translate('How Hamqadam Coins Work') }}</h2>
                <p class="fs-11 text-muted mb-4">{{ translate('Hamqadam Coins give you autonomy and respect family boundaries. Instead of recurring subscriptions that charge unpredictably, you invest coins only when you genuinely wish to initiate introductions.') }}</p>
                <div class="hq-coin-benefits">
                    <div><strong><i class="las la-heart mr-1"></i>{{ translate('Express Interests') }}</strong><small>{{ translate('Send formal proposals with personalized family intentions.') }}</small></div>
                    <div><strong><i class="las la-address-book mr-1"></i>{{ translate('Unlock Family Contacts') }}</strong><small>{{ translate('Access guardian phone numbers after mutual compatibility.') }}</small></div>
                    <div><strong><i class="las la-rocket mr-1"></i>{{ translate('Boost Profile Reach') }}</strong><small>{{ translate('Highlight biodata to verified guardians across your region.') }}</small></div>
                    <div><strong><i class="las la-images mr-1"></i>{{ translate('Unlock Protected Photos') }}</strong><small>{{ translate('View private galleries once permission is respectfully granted.') }}</small></div>
                </div>
            </div>
            <div class="hq-coin-table">
                <div><span>{{ translate('Express Formal Interest') }}</span><strong>{{ feature_coin_cost('express_interest', 10) }} {{ translate('Coins') }}</strong></div>
                <div><span>{{ translate('Direct Contact Access') }}</span><strong>{{ feature_coin_cost('view_contact', 100) }} {{ translate('Coins') }}</strong></div>
                <div><span>{{ translate('Weekly Spotlight Bump') }}</span><strong>{{ feature_coin_cost('profile_boost', 150) }} {{ translate('Coins') }}</strong></div>
                <div><span>{{ translate('Horoscope Deep Alignment') }}</span><strong>{{ feature_coin_cost('horoscope_match', 20) }} {{ translate('Coins') }}</strong></div>
            </div>
        </div>
    </div>
</section>

<section class="hq-design-section">
    <div class="container">
        <div class="hq-design-section-title">
            <span class="eyebrow">{{ translate('Side-by-side Comparison') }}</span>
            <h2>{{ translate('Compare Matrimonial Benefits') }}</h2>
            <p>{{ translate('Review all privileges to select the dignified path that suits your family timeline.') }}</p>
        </div>
        <div class="hq-benefit-table-wrap">
            <table class="hq-benefit-table">
                <thead>
                    <tr>
                        <th>{{ translate('Feature Details') }}</th>
                        @foreach($packages->take(4) as $package)
                            <th class="{{ $loop->iteration === 3 ? 'popular' : '' }}">{{ $package->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr><td>{{ translate('Investment / Fee') }}</td>@foreach($packages->take(4) as $package)<td>{{ $package->price > 0 ? single_price($package->price) : translate('Free') }}</td>@endforeach</tr>
                    <tr><td>{{ translate('Package Validity') }}</td>@foreach($packages->take(4) as $package)<td>{{ (int)$package->validity }} {{ translate('Days') }}</td>@endforeach</tr>
                    <tr><td>{{ translate('Coins Credited Instantly') }}</td>@foreach($packages->take(4) as $package)<td>{{ (int)$package->express_interest }}</td>@endforeach</tr>
                    <tr><td>{{ translate('Gallery Photo Limit') }}</td>@foreach($packages->take(4) as $package)<td>{{ (int)$package->photo_gallery }}</td>@endforeach</tr>
                    <tr><td>{{ translate('Direct Contact Info Views') }}</td>@foreach($packages->take(4) as $package)<td>{{ (int)$package->contact }}</td>@endforeach</tr>
                    <tr><td>{{ translate('Auto Profile Matching') }}</td>@foreach($packages->take(4) as $package)<td>{!! $package->auto_profile_match ? '<i class="las la-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>@endforeach</tr>
                    <tr><td>{{ translate('Auto Horoscope Matching') }}</td>@foreach($packages->take(4) as $package)<td>{!! $package->auto_horoscope_profile_match ? '<i class="las la-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>@endforeach</tr>
                    <tr><td>{{ translate('Advanced Filters') }}</td>@foreach($packages->take(4) as $package)@php($ff = (array)($package->feature_flags ?? []))<td>{!! !empty($ff['advanced_filters']) ? '<i class="las la-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>@endforeach</tr>
                    <tr><td>{{ translate('Priority Search / Boost') }}</td>@foreach($packages->take(4) as $package)@php($ff = (array)($package->feature_flags ?? []))<td>{!! !empty($ff['priority_search']) || !empty($ff['boost_profile']) ? '<i class="las la-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="hq-design-section hq-design-section-alt">
    <div class="container">
        <div class="hq-design-section-title">
            <span class="eyebrow">{{ translate('Family Clarifications') }}</span>
            <h2>{{ translate('Frequently Asked Questions') }}</h2>
            <p>{{ translate('Respectful transparency regarding payment process, safety, and matrimonial etiquette.') }}</p>
        </div>
        <div class="hq-faq">
            <details><summary>{{ translate('How do Direct Contact Views work?') }}</summary><p>{{ translate('Contact details remain private until the relevant entitlement is available and the platform privacy rules permit access.') }}</p></details>
            <details><summary>{{ translate('What payment methods are supported?') }}</summary><p>{{ translate('Available payment methods are shown securely at checkout and are controlled by the payment gateways enabled by administration.') }}</p></details>
            <details><summary>{{ translate('Is my photo and CNIC completely private?') }}</summary><p>{{ translate('Identity documents are used for verification and protected by the platform access and privacy rules. Public profile visibility follows your configured privacy permissions.') }}</p></details>
            <details><summary>{{ translate('Can our parents or guardians manage the profile?') }}</summary><p>{{ translate('Guardian Mode can be used where enabled so family members can assist with introductions under explicit permissions.') }}</p></details>
            <details><summary>{{ translate('How do promo codes and payment verification work?') }}</summary><p>{{ translate('Eligible promo codes are validated during checkout. Successful, pending, and failed payments are synchronized with the subscription entitlement state.') }}</p></details>
        </div>
        <div class="hq-support-cta mt-5">
            <div><h3>{{ translate('Need personalized matrimonial guidance?') }}</h3><p>{{ translate('Our compassionate family relationship counselors are available for confidential consultations across Pakistan and overseas.') }}</p></div>
            <div class="hq-support-cta-actions"><a class="btn btn-light" href="tel:{{ preg_replace('/[^0-9+]/', '', get_setting('header_helpline_no') ?: '+01112352566') }}"><i class="las la-phone mr-1"></i>{{ get_setting('header_helpline_no') ?: '+01 112 352 566' }}</a><a class="btn btn-primary" href="{{ route('contact_us') }}"><i class="lab la-whatsapp mr-1"></i>{{ translate('Chat or Get Support') }}</a></div>
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
