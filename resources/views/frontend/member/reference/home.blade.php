@extends('frontend.layouts.member_panel')

@section('panel_content')
@php
    $viewer = auth()->user();
    $member = $viewer->member;
    $avatar = $viewer->photo ? uploaded_asset($viewer->photo) : static_asset('assets/img/avatar-place.png');
    $location = $viewer->addresses()->where('type', 'present')->with('city')->first();
    $profileComplete = max(0, min(100, (int) $completion));
@endphp

<div class="hm-next hm-home-landing hm-home-refined">
    <nav class="hm-home-ref-tabs" aria-label="Member discovery shortcuts">
        <a class="is-active" href="{{ route('interest_requests') }}"><i class="las la-user-friends"></i> Proposals for You</a>
        <a href="{{ route('my_matched_profiles') }}"><i class="las la-magic"></i> AI Matches</a>
        <a href="{{ route('my_interests.index') }}"><i class="las la-heart"></i> My Interests</a>
        <a href="{{ route('profile-viewers.index') }}"><i class="las la-eye"></i> Viewed Me</a>
        <a href="{{ route('my_shortlists') }}"><i class="las la-users"></i> My Connections</a>
    </nav>

    <div class="hm-home-ref-layout">
        <main class="hm-home-ref-main">
            <section class="hm-home-ref-hero">
                <div class="hm-home-ref-hero-copy">
                    <span><i class="las la-magic"></i> Meaningful Matches</span>
                    <h1>Thoughtful proposals<br><em>for your journey</em></h1>
                    <p>Discover compatible members through your partner preferences.</p>
                    <a href="{{ route('member.listing') }}">Discover Members <i class="las la-arrow-right"></i></a>
                </div>
                <img src="{{ static_asset('assets/img/member-reference-couple.svg') }}" alt="Hamqadam couples illustration">
            </section>

            <form class="hm-home-ref-search" action="{{ route('member.listing') }}" method="GET" role="search">
                <i class="las la-search" aria-hidden="true"></i>
                <label class="sr-only" for="hm-home-ref-member-search">Search by member ID</label>
                <input id="hm-home-ref-member-search" name="member_code" type="search" placeholder="Find members by ID..." autocomplete="off">
                <button type="submit" aria-label="Search members"><i class="las la-sliders-h"></i></button>
            </form>

            <section class="hm-home-ref-recommend">
                <header>
                    <div><h2>Recommended for You</h2><p>Based on your preferences</p></div>
                    <a href="{{ route('my_matched_profiles') }}">View All <i class="las la-arrow-right"></i></a>
                </header>

                <div class="hm-home-ref-grid">
                    @forelse ($recommended as $match)
                        @php
                            $person = $match->matchedUser;
                            $personMember = $person?->member;
                        @endphp
                        @if ($person)
                            @php
                                $fallback = static_asset(($personMember?->gender ?? 1) == 1
                                    ? 'assets/img/avatar-place.png'
                                    : 'assets/img/female-avatar-place.png');
                                $photo = $person->photo && show_profile_picture($person)
                                    ? uploaded_asset($person->photo)
                                    : $fallback;
                                $age = $personMember?->birthday
                                    ? \Carbon\Carbon::parse($personMember->birthday)->age
                                    : null;
                                $address = $person->addresses->firstWhere('type', 'present');
                                $city = $address?->city?->name ?? 'Location not provided';
                                $profession = $personMember?->job_title ?: 'Profession not provided';
                                $education = $person->education->last()?->degree ?: 'Education not provided';
                                $matchScore = max(0, min(100, (int) round($match->match_percentage)));
                                $mustUpgrade = get_setting('full_profile_show_according_to_membership') == 1
                                    && $viewer->membership == 1;
                                $detailUrl = $mustUpgrade
                                    ? route('member.reference.packages')
                                    : route('member_profile', $person->id);
                            @endphp
                            <article class="hm-home-ref-card">
                                <a class="hm-home-ref-image" href="{{ $detailUrl }}">
                                    <img src="{{ $photo }}" loading="lazy" alt="{{ $person->first_name }} profile"
                                         onerror="this.onerror=null;this.src='{{ $fallback }}'">
                                    <span class="hm-home-ref-match"><i class="las la-heart"></i> {{ $matchScore }}% Match</span>
                                    @if (app(\App\Services\BadgeService::class)->isVerified($personMember))
                                        <span class="hm-home-ref-verified"><i class="las la-check-circle"></i> Verified</span>
                                    @endif
                                </a>
                                <div class="hm-home-ref-card-body">
                                    <h3>{{ trim($person->first_name . ' ' . $person->last_name) }}@if ($age), {{ $age }}@endif</h3>
                                    <p><i class="las la-map-marker-alt"></i> {{ $city }}</p>
                                    <p><i class="las la-briefcase"></i> {{ $profession }}</p>
                                    <p><i class="las la-graduation-cap"></i> {{ $education }}</p>
                                    <a class="hm-home-ref-view" href="{{ $detailUrl }}">{{ $mustUpgrade ? 'View Plans' : 'View Profile' }}</a>
                                </div>
                            </article>
                        @endif
                    @empty
                        <div class="hm-home-ref-empty">
                            <h3>No recommended profiles yet</h3>
                            <p>Complete your profile and preferences to discover compatible members.</p>
                            <a href="{{ route('profile_settings') }}">Update My Profile</a>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="hm-home-ref-sidebar">
            <section class="hm-home-ref-panel">
                <div class="hm-home-ref-panel-title"><h2>Profile Completion</h2><b>{{ $profileComplete }}%</b></div>
                <div class="hm-next-progress"><span style="width:{{ $profileComplete }}%"></span></div>
                <p>Make your profile more useful by completing your information and preferences.</p>
                <a class="hm-home-ref-button" href="{{ route('profile_settings') }}">Complete Your Profile <i class="las la-arrow-right"></i></a>
            </section>
            <section class="hm-home-ref-panel hm-home-ref-ai">
                <h2><i class="las la-robot"></i> Hamqadam AI Assistant</h2>
                <p>Get guidance, profile tips, and help along your matchmaking journey.</p>
                <a class="hm-home-ref-button" href="{{ route('member.reference.assistant') }}">Ask Hamqadam AI <i class="las la-arrow-right"></i></a>
            </section>
            <section class="hm-home-ref-panel">
                <h2>Quick Actions</h2>
                <div class="hm-home-ref-actions">
                    <a href="{{ route('profile_settings') }}"><i class="las la-user-edit"></i> Edit Profile</a>
                    <a href="{{ route('my_interests.index') }}"><i class="las la-heart"></i> My Interests</a>
                    <a href="{{ route('interest_requests') }}"><i class="las la-comment-dots"></i> My Proposals</a>
                    <a href="{{ route('my_ignored_list') }}"><i class="las la-ban"></i> Blocked Users</a>
                    <a href="{{ route('member.reference.settings') }}"><i class="las la-cog"></i> Account Settings</a>
                </div>
            </section>
            <section class="hm-home-ref-panel hm-home-ref-safety">
                <h2><i class="las la-shield-alt"></i> Your Safety Matters</h2>
                <p>We are committed to a safe, respectful and authentic community.</p>
                <a href="{{ route('client.scope.support') }}">Learn About Safety and Support →</a>
            </section>
        </aside>
    </div>
</div>
@endsection