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
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-wallet-match-2026.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-topnav-home.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-dashboard-referral-reference.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-settings-verification-home.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-four-reference.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-unified-header-blog.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-ux-polish.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-chat-reference-20261023.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-profile-sections-20261023.css') }}?v=1>
@php
    $memberShellUser = auth()->user();
    $memberShellAvatar = $memberShellUser?->photo ? uploaded_asset($memberShellUser->photo) : static_asset('assets/img/avatar-place.png');
@endphp
<div class="hm-member-app" style="--hm-promo-art:url('{{ static_asset('assets/img/member-reference-couple.svg') }}')">
  <header class="hm-member-topbar">
    <button type="button" class="hm-member-mobile-menu" aria-controls="hm-member-sidebar" aria-expanded="false" aria-label="Open dashboard navigation"><i class="las la-bars"></i></button>
    <a class="hm-member-topbar-brand" href="{{ route('dashboard') }}" aria-label="Hamqadam Dashboard"><span class="hm-member-heart"><i class="las la-heart"></i></span><span>Hamqadam<small>Risthon ki duniya</small></span></a>
    @php
      $primaryMemberNavigation = [
        ['label' => 'Home', 'route' => 'member.reference.home', 'active' => request()->routeIs('member.reference.home')],
        ['label' => 'Active Members', 'route' => 'member.listing', 'active' => request()->routeIs('member.listing')],
        ['label' => 'Premium Plans', 'route' => 'member.reference.packages', 'active' => request()->routeIs('member.reference.packages','packages')],
        ['label' => 'Happy Stories', 'route' => 'member.reference.stories', 'active' => request()->routeIs('member.reference.stories','member.reference.story.details')],
        ['label' => 'Help & Support', 'route' => 'client.scope.support', 'active' => request()->routeIs('client.scope.support','client.scope.support.*')],
      ];
    @endphp
    <nav class="hm-integrated-nav" aria-label="Main member navigation">
      @foreach($primaryMemberNavigation as $item)
        <a href="{{ route($item['route']) }}" class="hm-integrated-link {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
      @endforeach
    </nav>
    <div class="hm-integrated-search">
      <button id="hm-integrated-search-open" class="hm-integrated-search-trigger" type="button" aria-label="Open member search" aria-controls="hm-integrated-search-panel" aria-expanded="false"><i class="las la-search"></i></button>
      <div class="hm-integrated-search-panel" id="hm-integrated-search-panel" hidden>
        <form action="{{ route('member.listing') }}" method="GET" role="search" class="hm-integrated-search-form">
          <label class="sr-only" for="hm-integrated-search-input">Search members by member code</label>
          <i class="las la-search" aria-hidden="true"></i>
          <input id="hm-integrated-search-input" type="search" name="member_code" autocomplete="off" placeholder="Search members by ID..." value="{{ request('member_code') }}">
          <button class="hm-integrated-search-submit" type="submit" aria-label="Search members"><i class="las la-arrow-right"></i></button>
        </form>
        <button id="hm-integrated-search-close" type="button" class="hm-integrated-search-close" aria-label="Close member search"><i class="las la-times"></i></button>
      </div>
    </div>
    <div class="hm-accessibility-menu">
      <button type="button" class="hm-accessibility-toggle" id="hm-accessibility-toggle"
        aria-label="Accessibility options" aria-controls="hm-accessibility-dropdown"
        aria-expanded="false" aria-haspopup="true" title="Accessibility">
        <i class="las la-universal-access" aria-hidden="true"></i>
      </button>
      <div id="hm-accessibility-dropdown" class="hm-accessibility-dropdown" hidden>
        <div class="hm-accessibility-dropdown-heading"><strong>Accessibility</strong><button type="button" id="hm-accessibility-close" aria-label="Close accessibility options"><i class="las la-times"></i></button></div>
        @include('frontend.member.partials.accessibility_tools')
      </div>
    </div>
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
  const accessibilityToggle=root.querySelector('#hm-accessibility-toggle');
  const accessibilityDropdown=root.querySelector('#hm-accessibility-dropdown');
  const accessibilityClose=root.querySelector('#hm-accessibility-close');
  function setAccessibilityOpen(open){
    if(!accessibilityToggle||!accessibilityDropdown)return;
    accessibilityDropdown.hidden=!open;
    accessibilityToggle.setAttribute('aria-expanded',String(open));
    if(open)accessibilityDropdown.querySelector('select,input')?.focus();
    else accessibilityToggle.focus();
  }
  accessibilityToggle?.addEventListener('click',()=>setAccessibilityOpen(accessibilityDropdown.hidden));
  accessibilityClose?.addEventListener('click',()=>setAccessibilityOpen(false));
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&accessibilityDropdown&&!accessibilityDropdown.hidden)setAccessibilityOpen(false);
  });
  document.addEventListener('pointerdown',e=>{
    if(accessibilityDropdown&&!accessibilityDropdown.hidden&&!root.querySelector('.hm-accessibility-menu')?.contains(e.target)){
      accessibilityDropdown.hidden=true;
      accessibilityToggle?.setAttribute('aria-expanded','false');
    }
  });
  const openSearch=root.querySelector('#hm-integrated-search-open');
  const searchPanel=root.querySelector('#hm-integrated-search-panel');
  const closeSearch=root.querySelector('#hm-integrated-search-close');
  const searchField=root.querySelector('#hm-integrated-search-input');
  function toggleSearch(visible) {
    if(!searchPanel||!openSearch)return;
    searchPanel.hidden=!visible;
    root.classList.toggle('hm-search-open',visible);
    openSearch.setAttribute('aria-expanded',String(visible));
    if(visible)searchField?.focus();else openSearch.focus();
  }
  openSearch?.addEventListener('click',()=>toggleSearch(true));
  closeSearch?.addEventListener('click',()=>toggleSearch(false));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&searchPanel&&!searchPanel.hidden)toggleSearch(false);});
  document.addEventListener('pointerdown',e=>{
    if(searchPanel&&!searchPanel.hidden&&!root.querySelector('.hm-integrated-search')?.contains(e.target)) toggleSearch(false);
  });

})();
</script>
@endsection
