@php
    $referenceFooterPhone = get_setting('header_helpline_no') ?: '+92 300 1234567';
    $referenceFooterEmail = get_setting('footer_email') ?: 'support@hamqadam.com';
    $referenceFooterAddress = get_setting('footer_address') ?: 'Islamabad, Pakistan';
    $referenceSiteName = get_setting('website_name') ?: 'Hamqadam';
    $unseen_notification = 0;
    $unseen_chat_thread_count = 0;
@endphp

<footer class="hq-reference-footer mt-auto">
    <div class="container">
        <div class="hq-footer-grid">
            <div class="hq-footer-about">
                <a href="{{ route('home') }}" class="hq-footer-brand">
                    <span class="hq-footer-brand-mark"><img src="{{ static_asset('assets/img/hamqadam-double-heart.svg') }}?v=20261023" alt="" aria-hidden="true"></span>{{ $referenceSiteName }}
                </a>
                <p>{{ get_setting('about_us_description') ? strip_tags(get_setting('about_us_description')) : translate('Find Your Perfect Partner With Trust & Care. Hamqadam is a dignified, secure matrimonial platform committed to verified profiles, family respect, and lasting lifelong unions.') }}</p>
                @php
                    // Use standard Blade loops; the inline destructuring/one-line
                    // @php syntax formerly produced an unclosed compiled directive.
                    $footerSocialLinks = [
                        ['setting' => 'hq_social_facebook', 'label' => 'Facebook', 'icon' => 'lab la-facebook-f'],
                        ['setting' => 'hq_social_instagram', 'label' => 'Instagram', 'icon' => 'lab la-instagram'],
                        ['setting' => 'hq_social_x', 'label' => 'X', 'icon' => 'lab la-twitter'],
                        ['setting' => 'hq_app_store_url', 'label' => 'Apple App Store', 'icon' => 'lab la-apple'],
                        ['setting' => 'hq_play_store_url', 'label' => 'Google Play Store', 'icon' => 'lab la-google-play'],
                    ];
                @endphp
                <div class="hq-footer-social" aria-label="Social media and mobile applications" style="display:flex;flex-wrap:wrap;gap:10px;margin:14px 0">
                    @foreach ($footerSocialLinks as $footerSocialLink)
                        @php
                            $socialUrl = trim((string) get_setting($footerSocialLink['setting']));
                            $isValidSocialUrl = filter_var($socialUrl, FILTER_VALIDATE_URL)
                                && strtolower((string) parse_url($socialUrl, PHP_URL_SCHEME)) === 'https';
                        @endphp
                        @if ($isValidSocialUrl)
                            <a href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer"
                               aria-label="{{ $footerSocialLink['label'] }}" title="{{ $footerSocialLink['label'] }}"
                               style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid #a84469;border-radius:50%;color:#a84469">
                                <i class="{{ $footerSocialLink['icon'] }}" aria-hidden="true" style="font-size:20px"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
                <div class="hq-footer-trust">
                    <span><i class="las la-shield-alt"></i>{{ get_setting('hq_footer_verified_label') ?: translate('100% Verified Profiles') }}</span>
                    <span><i class="las la-lock"></i>{{ get_setting('hq_footer_ssl_label') ?: translate('256-Bit SSL Protection') }}</span>
                </div>
            </div>

            <div>
                <h4>{{ get_setting('hq_footer_discovery_heading') ?: 'Discovery' }}</h4>
                @foreach(\App\Support\PublicNavigation::items('footer') as $item)
                  <a href="{{ \App\Support\PublicNavigation::link($item['url']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div>
                <h4>{{ get_setting('hq_footer_trust_heading') ?: translate('Trust & Safety') }}</h4>
                @foreach(\App\Support\PublicNavigation::items('footer_trust') as $item)
                  <a href="{{ \App\Support\PublicNavigation::link($item['url']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div class="hq-footer-assistance">
                <h4>{{ get_setting('hq_footer_assistance_heading') ?: translate('Assistance') }}</h4>
                <p>{{ get_setting('hq_footer_support_label') ?: translate('Direct Dedicated Support') }}</p>
                <a class="hq-footer-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $referenceFooterPhone) }}">
                    <i class="las la-phone mr-1"></i>{{ $referenceFooterPhone }}
                </a>
                <a class="hq-footer-email" href="mailto:{{ $referenceFooterEmail }}"><i class="las la-envelope"></i><span>{{ $referenceFooterEmail }}</span></a>
                <div class="mt-2 text-muted fs-12">
                    <i class="las la-map-marker mr-1"></i>{{ strip_tags($referenceFooterAddress) }}
                </div>
                <small class="d-block mt-2">{{ get_setting('hq_footer_assistance_note') ?: translate('Available 24/7 for Family Consultations') }}</small>
            </div>
        </div>

        <div class="hq-footer-bottom">
            <span>{{ get_setting('footer_copyright_text') ? strip_tags(get_setting('footer_copyright_text')) : '© ' . date('Y') . ' ' . $referenceSiteName . ' Matrimonial Platform. All rights reserved.' }}</span>
            <div>
                @foreach(\App\Support\PublicNavigation::items('footer_bottom') as $item)
                  <a href="{{ \App\Support\PublicNavigation::link($item['url']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
</footer>

{{-- Navigation destinations require authentication. Guests do not see a fixed
     bottom navigation or its notification/message links. --}}
@auth
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top" style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important; ">
    <div class="row align-items-center gutters-5 text-center">
        <div class="col">
            <a href="{{ route('home') }}" class="text-reset d-block flex-grow-1 text-center py-2">
                <i class="las la-home fs-18 opacity-60 {{ areActiveRoutes(['home'],'opacity-100')}}"></i>
                <span class="d-block fs-10 opacity-60 {{ areActiveRoutes(['home'],'opacity-100 fw-600')}}">{{ translate('Home') }}</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('frontend.notifications') }}" class="text-reset d-block flex-grow-1 text-center py-2">
                <span class="d-inline-block position-relative px-2">
                    <i class="las la-bell fs-18 opacity-60 {{ areActiveRoutes(['frontend.notifications'],'opacity-100')}}"></i>
                    @php
                        $unseen_notification = (Auth::check() && Auth::user()->user_type == 'member')
                            ? \App\Models\Notification::where('notifiable_id', Auth::user()->id)->whereNull('read_at')->count()
                            : 0;
                    @endphp
                    @if(isset($unseen_notification) && $unseen_notification > 0)
                        <span class="badge badge-sm badge-circle badge-primary position-absolute absolute-top-right">{{ $unseen_notification }}</span>
                    @endif
                </span>
                <span class="d-block fs-10 opacity-60 {{ areActiveRoutes(['frontend.notifications'],'opacity-100 fw-600')}}">{{ translate('Notifications') }}</span>
            </a>
        </div>
        <div class="col">
          <a href="{{ route('all.messages') }}" class="text-reset d-block flex-grow-1 text-center py-2 {{ areActiveRoutes(['all.messages'],'opacity-100')}}">
              <span class="d-inline-block position-relative px-2">
                  <i class="las la-comment-dots fs-18 opacity-60 {{ areActiveRoutes(['all.messages'],'opacity-100')}}"></i>
                    @php
                        $unseen_chat_thread_count = (Auth::check() && Auth::user()->user_type == 'member')
                            ? count(chat_threads())
                            : 0;
                    @endphp
                    <span class="badge badge-sm badge-circle badge-primary position-absolute absolute-top-right chat-footer-badge" @if($unseen_chat_thread_count <= 0) style="display:none" @endif>{{ $unseen_chat_thread_count }}</span>
              </span>
              <span class="d-block fs-10 opacity-60 {{ areActiveRoutes(['all.messages'],'opacity-100 fw-600')}}">{{ translate('Messages') }}</span>
          </a>
        </div>
        @if (Auth::check())
            @if(Auth::user()->user_type == 'member')
                <div class="col">
                    <a href="javascript:void(0)" class="text-reset d-block flex-grow-1 text-center py-2 mobile-side-nav-thumb" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav">
                        <span class="d-block mx-auto mb-1 opacity-60">
                            <img src="{{ uploaded_asset(Auth::user()->photo)}}" class="rounded-circle size-20px" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        </span>
                        <span class="d-block fs-10 opacity-60">{{ translate('Account') }}</span>
                    </a>
                </div>
            @else
                <div class="col">
                    <a href="{{ route('admin.dashboard') }}" class="text-reset d-block flex-grow-1 text-center py-2">
                        <span class="d-block mx-auto mb-1 opacity-60">
                            <img src="{{ uploaded_asset(Auth::user()->photo)}}" class="rounded-circle size-20px" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        </span>
                        <span class="d-block fs-10 opacity-60">{{ translate('Account') }}</span>
                    </a>
                </div>
            @endif
        @else
            <div class="col">
                <a href="{{ route('login') }}" class="text-reset d-block flex-grow-1 text-center py-2">
                    <span class="d-block mx-auto mb-1 opacity-60 {{ areActiveRoutes(['login'],'opacity-100')}}">
                        <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="rounded-circle size-20px">
                    </span>
                    <span class="d-block fs-10 opacity-60 {{ areActiveRoutes(['login'],'opacity-100 fw-600')}}">{{ translate('Account') }}</span>
                </a>
            </div>
        @endif
    </div>
</div>
@endauth

@if (Auth::check() && Auth::user()->user_type == 'member')
    <div class="aiz-mobile-side-nav collapse-sidebar-wrap sidebar-xl d-xl-none z-1035">
        <div class="overlay dark c-pointer overlay-fixed" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb"></div>
        <div class="collapse-sidebar bg-white">
            @include('frontend.member.sidebar')
        </div>
    </div>
@endif
