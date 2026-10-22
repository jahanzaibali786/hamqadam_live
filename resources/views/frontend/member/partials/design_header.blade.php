@php
$title=$title??'Member Workspace'; $subtitle=$subtitle??'Manage your Hamqadam journey.'; $icon=$icon??'la-heart'; $kicker=$kicker??'Member Sanctuary';
@endphp
<div class="hm-subpage-header">
  <div class="hm-subpage-title">
    <span class="hm-eyebrow">{{ $kicker }}</span>
    <h1 class="hm-heading"><span class="hm-title-icon"><i class="las {{ $icon }}"></i></span> {{ $title }}</h1>
    <p class="hm-subtitle">{{ $subtitle }}</p>
  </div>
  <a href="{{ route('member.listing') }}" class="hm-subpage-discover"><i class="las la-search mr-1"></i> Explore Matches <i class="las la-arrow-right ml-1"></i></a>
</div>
