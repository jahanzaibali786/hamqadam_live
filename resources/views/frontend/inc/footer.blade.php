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
                    <span><i class="las la-heart"></i></span>{{ $referenceSiteName }}
                </a>
                <p>{{ translate('Find Your Perfect Partner With Trust & Care. Hamqadam is a dignified, secure matrimonial platform committed to verified profiles, family respect, and lasting lifelong unions.') }}</p>
                <div class="hq-footer-trust">
                    <span><i class="las la-shield-alt"></i>{{ translate('100% Verified Profiles') }}</span>
                    <span><i class="las la-lock"></i>{{ translate('256-Bit SSL Protection') }}</span>
                </div>
            </div>

            <div>
                <h4>{{ translate('Discovery') }}</h4>
                <a href="{{ route('member.listing') }}">{{ translate('Active Profiles') }}</a>
                <a href="{{ route('happy_stories') }}">{{ translate('Success Stories') }}</a>
                <a href="{{ route('packages') }}">{{ translate('Membership Packages') }}</a>
                <a href="{{ route('member.listing') }}">{{ translate('Advanced Search') }}</a>
            </div>

            <div>
                <h4>{{ translate('Trust & Safety') }}</h4>
                <a href="{{ url('/privacy-policy') }}">{{ translate('Privacy Policy') }}</a>
                <a href="{{ url('/terms-conditions') }}">{{ translate('Terms & Conditions') }}</a>
                <a href="{{ route('contact_us') }}">{{ translate('Identity Verification') }}</a>
                <a href="{{ route('contact_us') }}">{{ translate('Safe Matrimony Guide') }}</a>
            </div>

            <div class="hq-footer-assistance">
                <h4>{{ translate('Assistance') }}</h4>
                <p>{{ translate('Direct Dedicated Support') }}</p>
                <a class="hq-footer-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $referenceFooterPhone) }}">
                    <i class="las la-phone mr-1"></i>{{ $referenceFooterPhone }}
                </a>
                <a href="mailto:{{ $referenceFooterEmail }}">
                    <i class="las la-envelope mr-1"></i>{{ $referenceFooterEmail }}
                </a>
                <div class="mt-2 text-muted fs-12">
                    <i class="las la-map-marker mr-1"></i>{{ strip_tags($referenceFooterAddress) }}
                </div>
                <small class="d-block mt-2">{{ translate('Available 24/7 for Family Consultations') }}</small>
            </div>
        </div>

        <div class="hq-footer-bottom">
            <span>{{ get_setting('footer_copyright_text') ? strip_tags(get_setting('footer_copyright_text')) : '© ' . date('Y') . ' ' . $referenceSiteName . ' Matrimonial Platform. All rights reserved.' }}</span>
            <div>
                <a href="{{ url('/terms-conditions') }}">{{ translate('Terms') }}</a>
                <a href="{{ url('/privacy-policy') }}">{{ translate('Privacy') }}</a>
                <a href="{{ route('contact_us') }}">{{ translate('Help Center') }}</a>
            </div>
        </div>
    </div>
</footer>

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

@if (Auth::check() && Auth::user()->user_type == 'member')
    <div class="aiz-mobile-side-nav collapse-sidebar-wrap sidebar-xl d-xl-none z-1035">
        <div class="overlay dark c-pointer overlay-fixed" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb"></div>
        <div class="collapse-sidebar bg-white">
            @include('frontend.member.sidebar')
        </div>
    </div>
@endif
