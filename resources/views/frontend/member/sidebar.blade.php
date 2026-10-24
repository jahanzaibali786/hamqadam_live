@php
    $guardianLinks = \App\Models\FamilyGuardianLink::with(['profile.member.package'])
        ->where('guardian_user_id', Auth::id())
        ->where('status', 'approved')
        ->whereNull('revoked_at')
        ->whereNull('paused_at')
        ->get();
    $isDedicatedGuardian = \App\Models\FamilyGuardianLink::where('guardian_user_id', Auth::id())
        ->where('status', 'approved')
        ->whereNull('revoked_at')
        ->exists() && (Auth::user()->member == null || empty(Auth::user()->member->birthday));
    $firstManagedLink = $guardianLinks->first();
    $isPausedGuardian = $isDedicatedGuardian && $guardianLinks->isEmpty();
    $pausedLink = $isPausedGuardian ? \App\Models\FamilyGuardianLink::with(['profile.member'])->where('guardian_user_id', Auth::id())->where('status', 'approved')->whereNull('revoked_at')->whereNotNull('paused_at')->first() : null;

    $guardianPermissions = [];
    if ($firstManagedLink) {
        $guardianPermissions = \Illuminate\Support\Facades\DB::table('guardian_permissions')
            ->where('guardian_link_id', $firstManagedLink->id)
            ->where('is_allowed', 1)
            ->pluck('permission_key')
            ->toArray();
        if (empty($guardianPermissions) && !empty($firstManagedLink->permissions)) {
            $guardianPermissions = (array) $firstManagedLink->permissions;
        }
        if (empty($guardianPermissions)) {
            $guardianPermissions = array_map(fn($c) => $c->value, \App\Enums\GuardianPermission::DEFAULTS);
        }
    }
@endphp

<div class="aiz-user-sidenav-wrap hq-member-scroll-shell {{ $isDedicatedGuardian ? 'hm-guardian-nav' : 'hm-regular-nav' }} pt-4 sticky-top c-scrollbar-light position-relative z-1 shadow-none">
    <div class="absolute-top-left d-xl-none">
        <button class="btn btn-sm p-2" data-toggle="class-toggle" data-target=".aiz-mobile-side-nav"
            data-same=".mobile-side-nav-thumb">
            <i class="las la-times la-2x"></i>
        </button>
    </div>
    <div class="aiz-user-sidenav rounded overflow-hidden">
        <div class="px-4 text-center mb-3">
            <h4 class="h5 fw-600 mb-1">{{ Auth::user()->first_name . ' ' . Auth::user()->last_name }}</h4>
            @if ($isDedicatedGuardian)
                @if ($isPausedGuardian)
                    <span class="badge badge-inline badge-warning">{{ translate('Access Paused') }}</span>
                    @if ($pausedLink && $pausedLink->profile)
                        <div class="bg-soft-warning p-2 rounded text-left mt-3">
                            <span class="fs-10 text-muted text-uppercase fw-700 d-block">{{ translate('Assisting Member') }}</span>
                            <strong class="fs-13 d-block text-dark text-truncate">{{ $pausedLink->profile->first_name }} {{ $pausedLink->profile->last_name }}</strong>
                            <div class="fs-11 text-muted mt-1">
                                <i class="las la-pause-circle text-warning"></i> {{ translate('Access paused by member') }}
                            </div>
                        </div>
                    @endif
                @else
                    <span class="badge badge-inline badge-primary">{{ translate('Wali / Guardian') }}</span>
                    @if ($firstManagedLink && $firstManagedLink->profile)
                        <div class="bg-soft-primary p-2 rounded text-left mt-3">
                            <span class="fs-10 text-muted text-uppercase fw-700 d-block">{{ translate('Assisting Member') }}</span>
                            <strong class="fs-13 d-block text-primary text-truncate">{{ $firstManagedLink->profile->first_name }} {{ $firstManagedLink->profile->last_name }}</strong>
                            <div class="fs-11 text-muted mt-1">
                                <i class="las la-user-tag"></i> {{ $firstManagedLink->relationship }} · {{ translate($firstManagedLink->guardian_role ?? 'Guardian') }}
                            </div>
                            <div class="fs-11 text-muted mt-1">
                                <i class="las la-coins text-warning"></i> {{ $firstManagedLink->profile->member?->package?->name ?? translate('Free') }} · <strong>{{ $firstManagedLink->profile->member?->remaining_interest ?? 0 }} {{ translate('Coins') }}</strong>
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>

        <div class="sidemnenu hq-member-side-scroll mb-3" id="hq-member-side-scroll" tabindex="0" role="region" aria-label="Member dashboard navigation">
            <ul class="aiz-side-nav-list" data-toggle="aiz-side-menu">

                @if ($isDedicatedGuardian)
                    {{-- Dedicated Guardian Menu aligned with granted permissions --}}
                    <li class="aiz-side-nav-item">
                        <a href="{{ route('guardian_panel.index') }}" class="aiz-side-nav-link {{ areActiveRoutes(['guardian_panel.index']) }}">
                            <i class="las la-user-shield aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('Guardian Dashboard') }}</span>
                        </a>
                    </li>

                    {{-- Review Matches: view_recommended_matches or view_basic_profile --}}
                    @if ($firstManagedLink && (in_array('view_recommended_matches', $guardianPermissions) || in_array('view_basic_profile', $guardianPermissions)))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('guardian_panel.matches', ['profile_user_id' => $firstManagedLink->profile_user_id]) }}" class="aiz-side-nav-link {{ areActiveRoutes(['guardian_panel.matches']) }}">
                                <i class="las la-user-friends aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Review Matches') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Shortlist: shortlist_match --}}
                    @if (in_array('shortlist_match', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('my_shortlists') }}" class="aiz-side-nav-link {{ areActiveRoutes(['my_shortlists']) }}">
                                <i class="las la-list aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Shortlist') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Interests & Proposals: send_interest or review_proposal --}}
                    @if (in_array('send_interest', $guardianPermissions) || in_array('review_proposal', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('my_interests.index') }}" class="aiz-side-nav-link {{ areActiveRoutes(['my_interests.index', 'interest_requests']) }}">
                                <i class="la la-heart-o aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Interests & Proposals') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Family Introductions: approve_family_intro or review_proposal --}}
                    @if (in_array('approve_family_intro', $guardianPermissions) || in_array('review_proposal', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('guardian_mode.introductions') }}" class="aiz-side-nav-link {{ areActiveRoutes(['guardian_mode.introductions']) }}">
                                <i class="las la-handshake aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Family Introductions') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Gallery & Photos: view_photo or view_private_photos --}}
                    @if (in_array('view_photo', $guardianPermissions) || in_array('view_private_photos', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('gallery-image.index') }}" class="aiz-side-nav-link {{ areActiveRoutes(['gallery-image.index']) }}">
                                <i class="las la-image aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Gallery & Photos') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Chat & Messaging: view_private_chat --}}
                    @if (in_array('view_private_chat', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('all.messages') }}" class="aiz-side-nav-link {{ areActiveRoutes(['all.messages']) }}">
                                <i class="las la-envelope aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Messaging') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Package & Coins Usage: view_payments --}}
                    @if (in_array('view_payments', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('package_usage_history') }}" class="aiz-side-nav-link {{ areActiveRoutes(['package_usage_history', 'package_purchase_history']) }}">
                                <i class="las la-coins aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Package & Usage History') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Manage Other Guardians: manage_other_guardians --}}
                    @if (in_array('manage_other_guardians', $guardianPermissions))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route('guardian_mode.index') }}" class="aiz-side-nav-link {{ areActiveRoutes(['guardian_mode.index', 'guardian_mode.permissions.edit']) }}">
                                <i class="las la-users aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ translate('Manage Guardians') }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- Activity Audit Log --}}
                    <li class="aiz-side-nav-item">
                        <a href="{{ route('guardian_mode.activity') }}" class="aiz-side-nav-link {{ areActiveRoutes(['guardian_mode.activity']) }}">
                            <i class="las la-history aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('Activity Audit Log') }}</span>
                        </a>
                    </li>

                    {{-- Change Password --}}
                    <li class="aiz-side-nav-item">
                        <a href="{{ route('member.change_password') }}" class="aiz-side-nav-link {{ areActiveRoutes(['member.change_password']) }}">
                            <i class="las la-key aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('Change Password') }}</span>
                        </a>
                    </li>
                @else
                    {{-- Curated reference menu: one visible destination per feature. --}}
                    @php
                      $memberNav = [
                        ['dashboard','Dashboard','la-home'],
                        ['profile_settings','My Profile','la-user'],
                        ['__public_profile__','Public Profile','la-id-card'],
                        ['my_interests.index','My Interest','la-heart'],
                        ['my_shortlists','Shortlist','la-bookmark'],
                        ['all.messages','Messaging','la-comment-dots'],
                      ];
                      if(auth()->user()->member?->auto_profile_match == 1) $memberNav[] = ['my_matched_profiles','Matched Profiles','la-user-friends'];
                      $memberNav[] = ['interest_requests','Proposals','la-ring'];
                      $memberNav[] = ['client.scope.portal','Why This Match','la-magic'];
                      if(auth()->user()->member?->auto_horoscope_profile_match == 1) $memberNav[] = ['horoscope_matched_profiles','Horoscope Match','la-moon'];
                      $memberNav = array_merge($memberNav, [
                        ['member.listing','Advanced Search','la-search'],
                        ['profile-viewers.index','Profile Viewers','la-eye'],
                      ]);
                      if(get_setting('wallet_system')) $memberNav[] = ['wallet.index','My Wallet','la-wallet'];
                      $memberNav = array_merge($memberNav, [
                        ['member.reference.packages','Packages & Plans','la-crown'],
                        ['member.referrals.index','Referral Program','la-user-plus'],
                        ['member.reference.stories','Happy Stories','la-heart'],
                        ['member.reference.blogs','Blogs & Articles','la-book-open'],
                        ['client.scope.support','Support & Feedback','la-headset'],
                        ['member.verification','Verification','la-shield-alt'],
                        ['member.reference.meetings','Meeting Scheduling','la-calendar-alt'],
                        ['member.reference.assistant','AI Assistant','la-robot'],
                        ['frontend.notifications','Notifications','la-bell'],
                        ['member.reference.settings','Settings','la-cog'],
                      ]);
                      $extraMenu = [
                        ['member.v1_dashboard','AI Dashboard','la-brain'],
                        ['guardian_mode.index','Guardian Mode','la-users'],
                        ['guardian_panel.index','Guardian Panel','la-user-shield'],
                        ['gallery-image.index','Gallery & Photos','la-images'],
                        ['happy_story.member','My Submitted Stories','la-edit'],
                        ['member.payment_refunds.index','Payments & Refunds','la-file-invoice-dollar'],
                        ['package_purchase_history','Purchase History','la-receipt'],
                        ['package_usage_history','Package Usage History','la-history'],
                        ['my_ignored_list','Ignored Members','la-ban'],
                        ['client.scope.notifications','Notification Preferences','la-sliders-h'],
                        ['client.scope.satisfaction','Rate Hamqadam','la-star'],
                        ['member.change_password','Change Password','la-key'],
                      ];
                    @endphp

                    @foreach($memberNav as $navItem)
                      @php
                        $targetRoute = $navItem[0];
                        $isActiveNav = $targetRoute==='__public_profile__' ? request()->routeIs('member_profile') : request()->routeIs($targetRoute);
                        $navUrl = $targetRoute==='__public_profile__' ? route('member_profile', Auth::id()) : route($targetRoute);
                      @endphp
                      <li class="aiz-side-nav-item">
                        <a href="{{ $navUrl }}" class="aiz-side-nav-link {{ $isActiveNav?'active':'' }}"
                          @if($isActiveNav) aria-current="page" @endif>
                          <i class="las {{ $navItem[2] }} aiz-side-nav-icon" aria-hidden="true"></i>
                          <span class="aiz-side-nav-text">{{ $navItem[1] }}</span>
                          @if($targetRoute==='all.messages')
                            <span class="badge badge-primary badge-circle ml-auto unseen-chat-badge-sidebar" style="display:none">0</span>
                          @endif
                        </a>
                      </li>
                    @endforeach
                    {{-- Every permitted feature is visible as a normal navigation link; no hidden "More Features" accordion. --}}
                    @foreach($extraMenu as $navItem)
                      <li class="aiz-side-nav-item">
                        <a href="{{ route($navItem[0]) }}"
                           class="aiz-side-nav-link {{ request()->routeIs($navItem[0])?'active':'' }}"
                           @if(request()->routeIs($navItem[0])) aria-current="page" @endif>
                          <i class="las {{ $navItem[2] }} aiz-side-nav-icon" aria-hidden="true"></i>
                          <span class="aiz-side-nav-text">{{ $navItem[1] }}</span>
                        </a>
                      </li>
                    @endforeach
                    @if (get_setting('profile_picture_privacy') == 'only_me')
                      <li class="aiz-side-nav-item">
                        <a href="{{ route('profile-picture-view-request.index') }}" class="aiz-side-nav-link {{ request()->routeIs('profile-picture-view-request.*')?'active':'' }}">
                          <i class="las la-image aiz-side-nav-icon"></i><span class="aiz-side-nav-text">Picture View Requests</span>
                        </a>
                      </li>
                    @endif
                    @if (get_setting('gallery_image_privacy') == 'only_me')
                      <li class="aiz-side-nav-item">
                        <a href="{{ route('gallery-image-view-request.index') }}" class="aiz-side-nav-link {{ request()->routeIs('gallery-image-view-request.*')?'active':'' }}">
                          <i class="las la-images aiz-side-nav-icon"></i><span class="aiz-side-nav-text">Gallery View Requests</span>
                        </a>
                      </li>
                    @endif
                @endif
            </ul>
        </div>
        @unless($isDedicatedGuardian)
        @php
          $promo = match(true) {
            request()->routeIs('profile_settings') => ['Complete your profile. Get better matches.', 'Complete Now', 'profile_settings'],
            request()->routeIs('member.verification','member.reference.verification') => ['Verified profiles build stronger trust.', 'Get Verified Now', 'member.verification'],
            request()->routeIs('member.reference.assistant','member.v1_dashboard') => ['Let AI guide you to meaningful connections.', 'Explore AI Tools', 'member.reference.assistant'],
            request()->routeIs('member.reference.blogs') => ['Knowledge today. Better relationships tomorrow.', 'Explore Blogs', 'member.reference.blogs'],
            request()->routeIs('member.reference.stories','happy_story.member') => ['Real people. Real stories. Real happiness.', 'Share Your Story', 'happy_story.member'],
            request()->routeIs('frontend.notifications') => ['Stay updated. Never miss a meaningful connection.', 'View Plans', 'member.reference.packages'],
            request()->routeIs('member.reference.meetings') => ['Take the next step with confidence.', 'View Plans', 'member.reference.packages'],
            request()->routeIs('my_shortlists') => ['Your next chapter could be here.', 'Explore Matches', 'member.listing'],
            default => ['Find your story. Choose forever.', 'View Premium Plans', 'member.reference.packages'],
          };
        @endphp
        <div class="hm-member-context-promo">
          <img src="{{ static_asset('assets/img/member-reference-couple.svg') }}" alt="" loading="lazy">
          <p>{{ $promo[0] }}</p>
          <a href="{{ route($promo[2]) }}" class="hm-member-promo-cta">{{ $promo[1] }} <i class="las la-arrow-right"></i></a>
        </div>
        @endunless
        <div class="hm-member-sidebar-logout mb-3 px-3">
            <form action="{{ route('user.logout') }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-block btn-primary"><i class="las la-sign-out-alt"></i> {{ translate('Logout') }}</button>
            </form>
        </div>
    </div>
</div>
