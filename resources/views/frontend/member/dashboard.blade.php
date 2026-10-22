@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $currentUser=auth()->user();
 $member=$currentUser->member;
 $completion=max(0,min(100,(int)($member?->profile_completion_percentage??0)));
 $suggestions=collect($similar_profiles??[])->filter(fn($match)=>($match->match_percentage??0)>0 && $match->user)->sortByDesc('match_percentage')->take(4);
@endphp
<div class="hm-home-2026">
 <div class="hm-home-grid">
  <div class="hm-home-main">
   <nav class="hm-home-quicktabs" aria-label="Match shortcuts">
    <a class="is-active" href="{{ route('interest_requests') }}"><i class="las la-user-friends"></i> Proposals for You</a>
    <a href="{{ route('my_matched_profiles') }}"><i class="las la-magic"></i> AI Matches</a>
    <a href="{{ route('my_interests.index') }}"><i class="las la-heart"></i> My Interests</a>
    <a href="{{ route('profile-viewers.index') }}"><i class="las la-eye"></i> Viewed Me</a>
    <a href="{{ route('my_shortlists') }}"><i class="las la-users"></i> My Connections</a>
   </nav>
   <section class="hm-home-hero" aria-label="Discover meaningful proposals">
    <div class="hm-home-hero-copy"><small><i class="las la-magic"></i> AI Matches</small><h1>Thoughtful proposals <em>for your journey</em></h1><p>Explore compatible members based on your preferences and verified profile information.</p></div>
    <div class="hm-home-hero-image"><img src="{{ static_asset('assets/img/member-reference-couple.svg') }}" alt="Hamqadam meaningful relationships illustration"></div>
   </section>
   <form class="hm-home-search" action="{{ route('member.listing') }}" method="GET" role="search">
    <i class="las la-search" aria-hidden="true"></i>
    <label class="sr-only" for="hm-home-member-code">Find members by ID</label>
    <input id="hm-home-member-code" name="member_code" placeholder="Search members by ID or open advanced discovery..." type="search">
    <button type="submit" aria-label="Search members"><i class="las la-sliders-h"></i></button>
   </form>
   <section class="hm-home-results"><div class="hm-home-section-head"><div><h2>Recommended for You</h2><p>Based on your partner preferences</p></div><a href="{{ route('my_matched_profiles') }}">View All <i class="las la-arrow-right"></i></a></div>
    <div class="hm-home-cards">
     @forelse($suggestions as $match)
     @php
      $candidate=$match->user; $candidateMember=$candidate->member;
      $fallback=($candidateMember?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png';
      $photo=show_profile_picture($candidate)&&$candidate->photo?uploaded_asset($candidate->photo):static_asset($fallback);
      $name=trim($candidate->first_name.' '.$candidate->last_name);
      $canView=get_setting('full_profile_show_according_to_membership')!=1 || \App\Models\ProfileViewer::where('user_id',$candidate->id)->where('viewed_by',$currentUser->id)->exists() || (package_validity($currentUser->id)&&(int)get_remaining_package_value($currentUser->id,'remaining_profile_viewer_view')>0);
     @endphp
     <article class="hm-home-card">
      <div class="hm-home-card-photo"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ static_asset($fallback) }}'"><span class="hm-home-card-score"><i class="las la-heart"></i> {{ round($match->match_percentage) }}% Match</span></div>
      <div class="hm-home-card-body"><h3>{{ $name }}@if($candidateMember?->birthday), {{ \Carbon\Carbon::parse($candidateMember->birthday)->age }}@endif</h3>
      <p><i class="las la-briefcase"></i> {{ $candidateMember?->job_title ?: 'Hamqadam Member' }}</p>
      <p><i class="las la-id-card"></i> {{ $candidate->code }}</p>
      <a href="{{ $canView?route('member_profile',$candidate->id):route('packages') }}">{{ $canView?'View Profile':'Upgrade to View' }}</a></div>
     </article>
     @empty
      <div class="hm-home-empty"><h3>No matching profiles yet</h3><p>Complete your preferences to discover more compatible members.</p><a href="{{ route('profile_settings') }}">Update Your Preferences</a></div>
     @endforelse
    </div>
   </section>
  </div>
  <aside class="hm-home-rail">
   <section><div class="hm-home-progress-title"><h2>Profile Completion</h2><strong>{{ $completion }}%</strong></div><div class="hm-home-progress-track"><b style="width:{{ $completion }}%"></b></div>
    <div class="hm-home-check"><i class="las {{ $member?->birthday?'la-check-circle':'la-circle' }}"></i> Basic Information</div>
    <div class="hm-home-check"><i class="las {{ $currentUser->photo?'la-check-circle':'la-circle' }}"></i> Photos</div>
    <div class="hm-home-check"><i class="las {{ $member?->job_title?'la-check-circle':'la-circle' }}"></i> Education &amp; Profession</div>
    <div class="hm-home-check"><i class="las {{ $currentUser->partner_expectations?'la-check-circle':'la-circle' }}"></i> Partner Preferences</div>
    <div class="hm-home-check"><i class="las {{ $member?->ai_verification_status==='approved'?'la-check-circle':'la-circle' }}"></i> Verification</div>
    <a class="hm-home-rail-cta" href="{{ route('profile_settings') }}">Complete Your Profile <i class="las la-arrow-right"></i></a>
   </section>
   <section class="hm-home-ai"><i class="las la-robot"></i><h2>Hamqadam AI Assistant</h2><p>Get guidance, profile tips and help along your matchmaking journey.</p><a class="hm-home-rail-cta" href="{{ route('member.reference.assistant') }}">Ask Hamqadam AI <i class="las la-arrow-right"></i></a></section>
   <section><h2>Quick Actions</h2><div class="hm-home-rail-list"><a href="{{ route('profile_settings') }}"><i class="las la-edit"></i>Edit Profile</a><a href="{{ route('my_interests.index') }}"><i class="las la-heart"></i>My Interests</a><a href="{{ route('interest_requests') }}"><i class="las la-comment-dots"></i>My Proposals</a><a href="{{ route('my_ignored_list') }}"><i class="las la-ban"></i>Blocked Users</a><a href="{{ route('member.reference.settings') }}"><i class="las la-cog"></i>Account Settings</a></div></section>
   <section class="hm-home-safe"><h2><i class="las la-shield-alt"></i> Your Safety Matters</h2><p>We are committed to a safe, respectful and authentic community.</p><a href="{{ route('client.scope.support') }}">Learn About Safety and Support <i class="las la-arrow-right"></i></a></section>
  </aside>
 </div>
</div>
@endsection