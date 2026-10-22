@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
$user=auth()->user(); $member=$user->member;
$matches=collect($similar_profiles??[])->filter(fn($m)=>($m->match_percentage??0)>0&&$m->user)->take(3);
$progress=max(0,min(100,(int)($member?->profile_completion_percentage??0)));
$avatar=$user->photo?uploaded_asset($user->photo):static_asset('assets/img/avatar-place.png');
$coins=(int)($member?->remaining_interest??0);
$views=(int)($member?->remaining_profile_viewer_view??0);
$contact=(int)($member?->remaining_contact_view??0);
$gallery=(int)($member?->remaining_photo_gallery??0);
$package=$member?->package;
@endphp
<div class="hm-dashboard">
  <div class="hm-top">
    <div><div class="hm-eyebrow"><i class="las la-heart mr-1"></i> Hamqadam Member Sanctuary</div><h1 class="hm-heading">Assalamu Alaikum, {{ $user->first_name }}!</h1><p class="hm-subtitle">Welcome back. Here's your meaningful journey at a glance.</p></div>
    <nav class="hm-quick" aria-label="Quick member actions">
      <a href="{{ route('frontend.notifications') }}"><i class="las la-bell"></i> Notifications</a>
      <a href="{{ route('all.messages') }}"><i class="las la-envelope"></i> Messages</a>
      <a class="hm-pink" href="{{ route('member.listing') }}"><i class="las la-search"></i> Find Matches</a>
    </nav>
  </div>
  <div class="hm-kpis">
    <a href="{{ route('my_matched_profiles') }}" class="hm-kpi text-decoration-none"><div><small>Compatible Matches</small><div class="num">{{ count($similar_profiles??[]) }}</div><small>Explore suggestions</small></div><span class="hm-icon"><i class="las la-user-friends"></i></span></a>
    <a href="{{ route('interest_requests') }}" class="hm-kpi text-decoration-none"><div><small>Interest Requests</small><div class="num">{{ \App\Models\ExpressInterest::query()->where('user_id',$user->id)->count() }}</div><small>Received proposals</small></div><span class="hm-icon"><i class="las la-heart"></i></span></a>
    <a href="{{ route('my_shortlists') }}" class="hm-kpi text-decoration-none"><div><small>Shortlisted Members</small><div class="num">{{ \App\Models\Shortlist::query()->where('shortlisted_by',$user->id)->count() }}</div><small>People you saved</small></div><span class="hm-icon"><i class="las la-bookmark"></i></span></a>
    <a href="{{ route('wallet.index') }}" class="hm-kpi text-decoration-none"><div><small>Coin Balance</small><div class="num">{{ number_format($coins) }}</div><small>Available to connect</small></div><span class="hm-icon"><i class="las la-coins"></i></span></a>
  </div>
  <div class="hm-columns">
    <div>
      <section class="hm-card">
        <header class="hm-card-header"><h2><i class="las la-user-circle text-primary mr-2"></i> Profile Overview</h2><a href="{{ route('profile_settings') }}">Edit Profile <i class="las la-arrow-right"></i></a></header>
        <div class="hm-card-body">
          <div class="hm-profile-overview"><img class="hm-avatar" src="{{ $avatar }}" alt="{{ $user->first_name }} profile photo">
            <div style="flex:1;min-width:160px"><strong class="d-block" style="font-size:17px">{{ $user->first_name }} {{ $user->last_name }}</strong><span class="text-muted small">Member profile · {{ $package?->name ?? 'Free' }} plan</span><div class="mt-2"><span class="badge badge-soft-primary">{{ $user->approved?'Verified member':'Verification pending' }}</span> <span class="badge badge-light">{{ $progress }}% Complete</span></div></div>
          </div>
          <div class="hm-progress-row"><div class="hm-progress"><span style="width:{{ $progress }}%"></span></div><strong>{{ $progress }}%</strong></div>
          <p class="hm-subtitle mt-2 mb-3">Completing your profile helps other members understand you better.</p>
          <a class="btn btn-primary btn-sm" href="{{ route('profile_settings') }}">Complete My Profile <i class="las la-arrow-right ml-1"></i></a>
          @if(($member?->ai_verification_status??'')!=='approved')
            <a class="btn btn-outline-primary btn-sm ml-2" href="{{ route('member.verification') }}">Verify Identity</a>
          @endif
        </div>
      </section>
      <section class="hm-card">
        <header class="hm-card-header"><h2><i class="las la-heart text-primary mr-2"></i> Recommended Matches</h2><a href="{{ route('my_matched_profiles') }}">View All <i class="las la-arrow-right"></i></a></header>
        <div class="hm-card-body">
          @if($matches->isNotEmpty())
          <div class="hm-match-list">
            @foreach($matches as $match)
              @php $candidate=$match->user; $candidateName=trim($candidate->first_name.' '.$candidate->last_name); $fallback=($candidate->member?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png'; @endphp
              <article class="hm-match">
                <img loading="lazy" src="{{ show_profile_picture($candidate)&&$candidate->photo?uploaded_asset($candidate->photo):static_asset($fallback) }}" alt="{{ $candidateName }}">
                <div class="hm-match-info"><strong>{{ $candidateName }}</strong><small>{{ $candidate->member?->job_title ?: 'Hamqadam member' }}</small><span class="hm-match-score">{{ round($match->match_percentage) }}% Compatible</span><a href="{{ route('member_profile',$candidate->id) }}" class="btn btn-outline-primary btn-block">View Profile</a></div>
              </article>
            @endforeach
          </div>
          @else <div class="hm-empty">No compatible matches available yet. Complete your partner preferences to improve suggestions.<div class="mt-3"><a href="{{ route('member.listing') }}" class="btn btn-outline-primary btn-sm">Explore Members</a></div></div>
          @endif
        </div>
      </section>
      <section class="hm-card">
        <header class="hm-card-header"><h2><i class="las la-bolt text-primary mr-2"></i> Quick Actions</h2></header>
        <div class="hm-card-body hm-actions">
          <a href="{{ route('member.listing') }}" class="hm-action"><i class="las la-search"></i><div><b>Explore Members</b><span>Find your compatible partner</span></div></a>
          <a href="{{ route('my_shortlists') }}" class="hm-action"><i class="las la-bookmark"></i><div><b>My Shortlist</b><span>Saved member profiles</span></div></a>
          <a href="{{ route('all.messages') }}" class="hm-action"><i class="las la-comments"></i><div><b>Messages</b><span>Continue conversations</span></div></a>
          <a href="{{ route('my_interests.index') }}" class="hm-action"><i class="las la-heart"></i><div><b>My Proposals</b><span>Track your interests</span></div></a>
          <a href="{{ route('client.scope.portal') }}" class="hm-action"><i class="las la-users"></i><div><b>Match & Rewards</b><span>Compatibility and Got Match</span></div></a>
          <a href="{{ route('member.v1_dashboard') }}" class="hm-action"><i class="las la-robot"></i><div><b>AI Dashboard</b><span>Smart discovery insights</span></div></a>
        </div>
      </section>
    </div>
    <div>
      <section class="hm-card"><header class="hm-card-header"><h2><i class="las la-gem text-primary mr-2"></i> My Membership</h2><a href="{{ route('packages') }}">Plans <i class="las la-arrow-right"></i></a></header><div class="hm-card-body"><div class="hm-plan"><small>YOUR CURRENT PLAN</small><div><strong>{{ $package?->name??'Free Membership' }}</strong></div><p>{{ $member?->package_validity?'Valid until '.$member->package_validity:'Discover additional benefits with a plan.' }}</p><a href="{{ route('packages') }}">Explore Plans →</a></div><div class="d-flex justify-content-between mt-3 small"><span>Profile views remaining</span><strong>{{ $views }}</strong></div><div class="d-flex justify-content-between mt-2 small"><span>Contact views remaining</span><strong>{{ $contact }}</strong></div><div class="d-flex justify-content-between mt-2 small"><span>Gallery uploads</span><strong>{{ $gallery }}</strong></div></div></section>
      <section class="hm-card"><header class="hm-card-header"><h2><i class="las la-bell text-primary mr-2"></i> Recent Activity</h2><a href="{{ route('frontend.notifications') }}">View All</a></header><div class="hm-card-body"><ul class="hm-activity">@forelse($user->notifications()->latest()->limit(4)->get() as $notification)<li><i class="las la-bell"></i><div>{{ data_get($notification->data,'message',data_get($notification->data,'title','Account update')) }}<div class="text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div></div></li>@empty<li><i class="las la-info-circle"></i><div>No new activity yet. You'll see updates here.</div></li>@endforelse</ul></div></section>
      <section class="hm-card"><header class="hm-card-header"><h2><i class="las la-shield-alt text-primary mr-2"></i> Account Safety</h2></header><div class="hm-card-body"><div class="hm-security"><i class="las la-check-circle"></i><div><strong>Privacy & verification controls</strong><p class="mb-0">Manage visibility, security and identity status.</p></div></div><a class="btn btn-outline-primary btn-block btn-sm mt-3" href="{{ route('profile_settings') }}">Manage Profile Privacy</a></div></section>
      <section class="hm-card"><header class="hm-card-header"><h2><i class="las la-headset text-primary mr-2"></i> Need Assistance?</h2></header><div class="hm-card-body"><p class="hm-subtitle">Our support team is here to help you along your journey.</p><a href="{{ route('client.scope.support') }}" class="btn btn-outline-primary btn-block btn-sm mt-3">Contact Support</a></div></section>
    </div>
  </div>
</div>
@endsection
