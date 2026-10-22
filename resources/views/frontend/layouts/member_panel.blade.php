@extends('frontend.layouts.app')
@section('member_dashboard_shell', '1')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-sidebar-scroll.css') }}?v=4">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-suite.css') }}?v=3">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-reference-v2.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-workspace-scroll.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-reference-finish.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-reference-final.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-parity.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-sidebar-viewport-fix.css') }}?v=2">
@php
    $memberShellUser = auth()->user();
    $memberShellAvatar = $memberShellUser?->photo ? uploaded_asset($memberShellUser->photo) : static_asset('assets/img/avatar-place.png');
@endphp
<div class="hm-member-app" style="--hm-promo-art:url('{{ static_asset('assets/img/member-reference-couple.svg') }}')">
  <header class="hm-member-topbar">
    <button type="button" class="hm-member-mobile-menu" aria-controls="hm-member-sidebar" aria-expanded="false" aria-label="Open dashboard navigation"><i class="las la-bars"></i></button>
    <a class="hm-member-topbar-brand" href="{{ route('dashboard') }}" aria-label="Hamqadam Dashboard"><span class="hm-member-heart"><i class="las la-heart"></i></span><span>Hamqadam<small>Risthon ki duniya</small></span></a>
    <form action="{{ route('member.listing') }}" method="GET" role="search" class="hm-member-topbar-search">
      <label for="hm-member-search" class="sr-only">Search member code</label>
      <i class="las la-search"></i>
      <input id="hm-member-search" type="search" name="member_code" placeholder="Search profiles by member ID" value="{{ request('member_code') }}">
      <button type="submit" aria-label="Search"><i class="las la-arrow-right"></i></button>
    </form>
    <nav class="hm-member-topbar-actions" aria-label="Member shortcuts">
      <a href="{{ route('my_shortlists') }}" title="Shortlist" aria-label="My shortlist"><i class="las la-heart"></i></a>
      <a href="{{ route('all.messages') }}" title="Messages" aria-label="Messages"><i class="las la-comment-dots"></i></a>
      <a href="{{ route('frontend.notifications') }}" title="Notifications" aria-label="Notifications"><i class="las la-bell"></i></a>
      <a class="hm-member-account" href="{{ route('profile_settings') }}">
        <img src="{{ $memberShellAvatar }}" alt="My profile photo">
        <span>{{ $memberShellUser?->first_name }} {{ $memberShellUser?->last_name }}</span>
        <i class="las la-angle-down"></i>
      </a>
    </nav>
  </header>
  <section class="hq-member-panel-page hq-member-sticky-layout hm-member-stage">
    <div class="hm-member-app-layout">
      <aside class="hm-sidebar-column" id="hm-member-sidebar" aria-label="Account navigation">
        @include('frontend.member.sidebar')
      </aside>
      <button type="button" class="hm-member-overlay" aria-label="Close dashboard navigation"></button>
      <main class="aiz-user-panel hq-member-main-content hm-member-workspace" id="hm-member-workspace">
        @include('frontend.member.partials.accessibility_tools')
        @yield('panel_content')
      </main>
    </div>
  </section>
</div>
<script src="{{ static_asset('assets/js/hamqadam-member-sidebar-scroll.js') }}?v=4" defer></script>
<script>
(function () {
  const root=document.querySelector('.hm-member-app'),toggle=root?.querySelector('.hm-member-mobile-menu'),overlay=root?.querySelector('.hm-member-overlay');
  if(!root||!toggle)return;
  const close=()=>{root.classList.remove('hm-nav-open');toggle.setAttribute('aria-expanded','false');};
  toggle.addEventListener('click',()=>{const opened=root.classList.toggle('hm-nav-open');toggle.setAttribute('aria-expanded',String(opened));});
  overlay?.addEventListener('click',close);
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
  root.querySelectorAll('.hm-sidebar-column a[href]').forEach(link=>link.addEventListener('click',close));
})();
</script>
@endsection
