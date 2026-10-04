@php
    $referenceHelpline = get_setting('header_helpline_no') ?: '+01 112 352 566';
@endphp
<div class="hq-reference-header @if(get_setting('header_stikcy') == 'on') position-fixed @else position-absolute @endif w-100 top-0 z-1020">
    <div class="hq-reference-topbar d-none d-lg-block"><div class="container d-flex align-items-center justify-content-between"><div><span class="mr-4"><i class="las la-headset mr-1"></i>{{ translate('Helpline') }}: <strong>{{ $referenceHelpline }}</strong></span><span class="hq-verified-community"><i class="las la-shield-alt mr-1"></i>{{ translate('100% ID Verified Community') }}</span></div><div class="hq-top-links"><span><i class="las la-globe mr-1"></i>{{ translate('English') }}</span><span>/</span><a href="{{ route('contact_us') }}">{{ translate('FAQ') }}</a><a href="{{ route('happy_stories') }}">{{ translate('Success Stories') }}</a></div></div></div>
    <header class="hq-reference-navbar">
        <div class="container hq-reference-navbar-inner">
            <a href="{{ route('home') }}" class="hq-reference-brand">
                <span class="hq-reference-brand-mark"><i class="las la-heart"></i></span>
                <span><strong>{{ get_setting('website_name') ?: 'Hamqadam' }}</strong><small>{{ translate('Matrimonial Sanctuary') }}</small></span>
            </a>
            <nav class="hq-reference-menu d-none d-lg-flex">
                <a class="{{ areActiveRoutes(['home'], 'is-active') }}" href="{{ route('home') }}">{{ translate('Home') }}</a>
                <a class="{{ areActiveRoutes(['member.listing'], 'is-active') }}" href="{{ route('member.listing') }}">{{ translate('Active Members') }}</a>
                <a class="{{ areActiveRoutes(['packages'], 'is-active') }}" href="{{ route('packages') }}">{{ translate('Premium Plans') }}</a>
                <a class="{{ areActiveRoutes(['happy_stories'], 'is-active') }}" href="{{ route('happy_stories') }}">{{ translate('Happy Stories') }}</a>
                <a class="{{ areActiveRoutes(['contact_us'], 'is-active') }}" href="{{ route('contact_us') }}">{{ translate('Help & Support') }}</a>
            </nav>
            <div class="hq-reference-actions">
                @auth
                    @if(auth()->user()->user_type === 'member')
                        @php
                            $unseen_notif = \App\Models\Notification::where('notifiable_id', auth()->id())->whereNull('read_at')->count();
                            $unseen_chat = count(chat_threads());
                        @endphp
                        <a class="hq-header-icon d-none d-lg-inline-flex position-relative" href="{{ route('frontend.notifications') }}" title="{{ translate('Notifications') }}">
                            <i class="las la-bell"></i>
                            @if($unseen_notif > 0)
                                <span class="badge badge-sm badge-circle badge-primary position-absolute" style="top:-3px;right:-3px;font-size:9px;padding:2px 4px;">{{ $unseen_notif }}</span>
                            @endif
                        </a>
                        <a class="hq-header-icon d-none d-lg-inline-flex position-relative" href="{{ route('all.messages') }}" title="{{ translate('Messages') }}">
                            <i class="las la-comment-dots"></i>
                            @if($unseen_chat > 0)
                                <span class="badge badge-sm badge-circle badge-primary position-absolute chat-header-badge" style="top:-3px;right:-3px;font-size:9px;padding:2px 4px;">{{ $unseen_chat }}</span>
                            @endif
                        </a>
                        <a class="hq-dashboard-pill" href="{{ route('dashboard') }}">
                            <img src="{{ uploaded_asset(auth()->user()->photo) }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';" class="hq-nav-avatar rounded-circle">
                            <span class="hq-nav-user-name">{{ auth()->user()->first_name ?: translate('Dashboard') }}</span>
                            <i class="las la-angle-right ml-1 opacity-60"></i>
                        </a>
                    @else
                        <a class="hq-dashboard-pill" href="{{ route('admin.dashboard') }}">
                            <i class="las la-tachometer-alt mr-1"></i>
                            <span>{{ translate('Admin Panel') }}</span>
                        </a>
                    @endif
                    <a class="hq-logout-button" href="{{ route('user.logout') }}" title="{{ translate('Logout') }}">
                        <i class="las la-sign-out-alt mr-1"></i>{{ translate('Logout') }}
                    </a>
                @else
                    <a class="hq-login-button" href="{{ route('login') }}">{{ translate('Log In') }}</a>
                    <a class="hq-register-button" href="{{ route('register') }}">{{ translate('Register Now') }}</a>
                @endauth
            </div>
        </div>
        <div class="hq-reference-mobile-menu d-lg-none">
            <a href="{{ route('home') }}">{{ translate('Home') }}</a>
            <a href="{{ route('member.listing') }}">{{ translate('Members') }}</a>
            <a href="{{ route('packages') }}">{{ translate('Plans') }}</a>
            <a href="{{ route('contact_us') }}">{{ translate('Support') }}</a>
        </div>
    </header>
</div>
<div class="hq-legacy-header">
<div class="hq-site-header @if(get_setting('header_stikcy') == 'on') position-fixed @else position-absolute @endif w-100 top-0 z-1020">
    <div class="top-navbar bg-white border-bottom z-1035 py-2 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 col">
                    <ul class="list-inline d-flex align-items-center justify-content-between justify-content-lg-start mb-0">
                        <li class="list-inline-item mr-4 hq-community-note">
                            <i class="las la-shield-alt mr-1"></i>{{ translate('100% ID Verified Community') }}
                        </li>
                        <li class="list-inline-item">
                          <a href="{{ get_setting('header_left_quick_link1') }}" class="text-reset opacity-60">
                            <span>{{ get_setting('header_left_quick_link1_text') }}</span>
                          </a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-7 col">
                    <ul class="list-inline mb-0 d-flex align-items-center justify-content-end ">
                        <li class="list-inline-item mr-3 pr-3 border-right text-reset opacity-60">
                            <span>{{ translate('Help Line') }}</span>
                            <span>{{ get_setting('header_helpline_no') }}</span>
                        </li>
                        @if (Auth::check())
                        <li class="list-inline-item dropdown">
                            @php
                            $notifications = \App\Models\Notification::latest()->where('notifiable_id',Auth()->user()->id)->take(10)->get();
                            $unseen_notification = \App\Models\Notification::where('notifiable_id',Auth()->user()->id)->where('read_at',null)->count();
                            @endphp
                            <a href="javascript:void(0)" class="dropdown-toggle text-reset no-arrow p-5px"
                                data-toggle="dropdown" data-display="static">
                                <i class="las la-bell fs-16 opacity-60"></i>
                                @if($unseen_notification > 0)
                                <span class="badge badge-dot badge-sm badge-status no-border badge-primary"></span>
                                @endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0">
                                <div class="p-3 bg-light border-bottom">
                                    <h6 class="mb-0">{{ translate('Notifications') }}</h6>
                                </div>
                                <ul class="list-group list-group-raw c-scrollbar-light"
                                    style="overflow-y:auto;max-height:300px;">
                                    @include('frontend.inc.notification')
                                </ul>
                                <div class="border-top">
                                    <a href="{{ route('frontend.notifications') }}"
                                        class="btn text-reset btn-block">{{ translate('View All Notifications') }}</a>
                                </div>
                            </div>
                        </li>
                        @php
                        $unseen_chat_threads = chat_threads();
                        $unseen_chat_thread_count = count($unseen_chat_threads);
                        @endphp
                        <li class="list-inline-item dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle text-reset no-arrow p-5px"
                                data-toggle="dropdown" data-display="static">
                                <i class="las la-envelope fs-16 opacity-60"></i>
                                @if($unseen_chat_thread_count > 0)
                                <span class="badge badge-dot badge-sm badge-status no-border badge-primary chat-header-badge"></span>
                                @else
                                <span class="badge badge-dot badge-sm badge-status no-border badge-primary chat-header-badge" style="display:none"></span>
                                @endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0">
                                <div class="p-3 bg-light border-bottom">
                                    <h6 class="mb-0">{{ translate('Messages') }}</h6>
                                </div>

                                <div class="c-scrollbar-light" style="overflow-y:auto;max-height:300px;">
                                    @forelse ($unseen_chat_threads as $key => $chat_thread_id)
                                    @php
                                    $chat = \App\Models\Chat::where('chat_thread_id', $chat_thread_id)->latest()->first();
                                    $current_user = Auth::user()->id;
                                    @endphp

                                    @if ($chat != null)
                                    <a href="{{ route('all.messages') }}"
                                        class="chat-user-item p-3 d-block text-inherit hov-bg-soft-primary">
                                        <div class="media">
                                            <span class="avatar avatar-sm mr-3 flex-shrink-0">
                                                @if($current_user == $chat->chatThread->sender->id)
                                                @php $user_to_show = 'receiver'; @endphp
                                                @else
                                                @php $user_to_show = 'sender'; @endphp
                                                @endif
                                                @if ($chat->chatThread->$user_to_show->photo != null)
                                                <img src="{{ uploaded_asset($chat->chatThread->$user_to_show->photo) }}">
                                                @else
                                                <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                                                @endif
                                                @if(Cache::has('user-is-online-' . $chat->chatThread->$user_to_show->id))
                                                <span
                                                    class="badge badge-dot badge-circle badge-success badge-status badge-md"></span>
                                                @else
                                                <span
                                                    class="badge badge-dot badge-circle badge-secondary badge-status badge-md"></span>
                                                @endif
                                            </span>
                                            <div class="media-body minw-0">
                                                <h6 class="mt-0 mb-1 fs-14 text-truncate">
                                                    {{ $chat->chatThread->$user_to_show->first_name.' '.$chat->chatThread->$user_to_show->last_name }}
                                                </h6>
                                                @if ($chat->message != null)
                                                <div class="fs-12 text-truncate opacity-60">{{ $chat->message }}</div>
                                                @else
                                                <div class="fs-12 text-truncate opacity-60">{{ translate('Attachments') }}
                                                </div>
                                                @endif
                                            </div>
                                            <div class="ml-2 text-right">
                                                <div class="opacity-60 fs-10 mb-1">
                                                    {{ Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </a>
                                    @endif
                                    @empty
                                    <div class="text-center py-4">
                                        <i class="las la-frown la-4x mb-2 opacity-40"></i>
                                        <h4 class="h6">{{ translate('No New Messages') }}</h4>
                                    </div>
                                    @endforelse
                                </div>
                                <div class="border-top">
                                    <a href="{{ route('all.messages') }}"
                                        class="btn text-reset btn-block">{{ translate('View All Messages') }}</a>
                                </div>
                            </div>
                        </li>
                        <li class="list-inline-item mx-4">
                            <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-reset">
                                <img src="{{ uploaded_asset(Auth::user()->photo) }}"
                                    class="size-30px rounded-circle img-fit mr-2"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                <span class="opacity-60 mr-1">
                                    {{ translate('Hi') }},
                                </span>
                                <span class="text-primary-grad fw-700">
                                    {{ Auth::user()->first_name }}
                                </span>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('user.logout') }}"
                                class="btn btn-sm bg-primary-grad text-white fw-600 py-1 border round-btn">{{translate('Logout')}}</a>
                        </li>
                        @else
                        <li class="list-inline-item ml-4">
                            <a class="text-reset opacity-60" href="{{ route('login') }}">{{ translate('Log In') }}</a>
                        </li>
                        <li class="list-inline-item ml-3">
                            <a class="btn btn-sm bg-primary-grad text-white fw-600 py-1 border round-btn"
                                href="{{ route('register') }}">{{ translate('Registration') }}</a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <header
        class="aiz-header shadow-md bg-white border-gray-300">
        <div class="aiz-navbar position-relative">
            <div class="container">
                <div class="d-lg-flex justify-content-between text-center text-lg-left">
                    <div class="logo">
                        <a href="{{ route('home') }}" class="d-inline-block">
                            @if(get_setting('header_logo') != null)
                            <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="{{ env('APP_NAME') }}"
                                class="mw-100 h-30px"style="width: 140px; height:80px;">
                            @else
                            <img src="{{ static_asset('assets/img/logo.png') }}" alt="{{ env('APP_NAME') }}"
                                class="mw-100 h-30px"style="width: 140px; height:80px;">
                            @endif
                        </a>
                    </div>
                    <ul
                        class="mb-0 pl-0 ml-lg-auto d-lg-flex align-items-stretch justify-content-center justify-content-lg-start mobile-hor-swipe">
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['home'],'bg-primary-grad') }}">
                            <a class="nav-link text-uppercase fw-700 fs-15 d-flex align-items-center bg-white py-2"
                                href="{{ route('home') }}">
                                <span class="text-primary-grad mb-n1">{{ translate('Home') }}</span>
                            </a>
                        </li>
                        <li
                            class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['member.listing'],'bg-primary-grad') }}">
                            <a class="nav-link text-uppercase fw-700 fs-15 d-flex align-items-center bg-white py-2"
                                href="{{ route('member.listing') }}">
                                <span class="text-primary-grad mb-n1">{{ translate('Active Members') }}</span>
                            </a>
                        </li>
                        <li class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['packages'],'bg-primary-grad') }}">
                            <a class="nav-link text-uppercase fw-700 fs-15 d-flex align-items-center bg-white py-2"
                                href="{{ route('packages') }}">
                                <span class="text-primary-grad mb-n1">{{ translate('Premium Plans') }}</span>
                            </a>
                        </li>
                        <li
                            class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['happy_stories'],'bg-primary-grad') }}">
                            <a class="nav-link text-uppercase fw-700 fs-15 d-flex align-items-center bg-white py-2"
                                href="{{ route('happy_stories') }}">
                                <span class="text-primary-grad mb-n1">{{ translate('Happy Stories') }}</span>
                            </a>
                        </li>
                        <li
                            class="d-inline-block d-lg-flex pb-1 {{ areActiveRoutes(['contact_us'],'bg-primary-grad') }}">
                            <a class="nav-link text-uppercase fw-700 fs-15 d-flex align-items-center bg-white py-2"
                                href="{{ route('contact_us') }}">
                                <span class="text-primary-grad mb-n1">{{ translate('Ticket') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        @if (Auth::check() && auth()->user()->user_type == 'member')
            <div class="border-top d-none d-lg-block">
                <div class="container">
                    <ul class="list-inline d-flex align-items-center mb-0">
                        <li class="list-inline-item">
                            <a href="{{ route('dashboard') }}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['dashboard'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('Dashboard') }}</span>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('profile_settings') }}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['profile_settings'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('My Profile') }}</span>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('my_interests.index') }}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['my_interests.index' ,'express-interest.index'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('My Interest') }}</span>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{route('my_shortlists')}}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['my_shortlists'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('Shortlist') }}</span>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('all.messages') }}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['all.messages'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('Messaging') }}</span>
                            </a>
                        </li>
                        @if(Auth::user()->member->auto_profile_match == 1)
                            <li class="list-inline-item">
                                <a href="{{ route('my_matched_profiles') }}"
                                    class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['my_matched_profiles'],'text-primary-grad opacity-100') }}">
                                    <span>{{ translate('Matched Profile') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(Auth::user()->member->auto_horoscope_profile_match == 1)
                            <li class="list-inline-item">
                                <a href="{{ route('horoscope_matched_profiles') }}"
                                    class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['horoscope_matched_profiles'],'text-primary-grad opacity-100') }}">
                                    <span>{{ translate('Horoscope Matched Profile') }}</span>
                                </a>
                            </li>
                        @endif
                        <li class="list-inline-item">
                            <a href="{{ route('profile-viewers.index') }}"
                                class="text-reset d-inline-block px-4 py-3 fw-600 {{ areActiveRoutes(['profile-viewers.index'],'text-primary-grad opacity-100') }}">
                                <span>{{ translate('Profile Viewers') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </header>
</div>
</div>
