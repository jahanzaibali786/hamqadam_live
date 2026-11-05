{{-- Compact card used by both Dashboard and Matched Profiles, with the same
     reference-based density, all existing actions and role-specific colors. --}}
@php
    $cardMember = $person->member;
    $cardAddress = $person->addresses->firstWhere('type', 'present');
    $cardAge = $cardMember?->birthday ? \Carbon\Carbon::parse($cardMember->birthday)->age : null;
    $cardReligion = $person->spiritual_backgrounds?->religion?->name;
    $cardEducation = $person->education->last()?->degree;
    $cardVerified = app(\App\Services\BadgeService::class)->isVerified($cardMember);
    $cardMatchPercentage = max(0, min(100, round((float) $match->match_percentage)));
    $matchActions = \App\Support\MemberMatchCardActions::between(auth()->user(), $person);
@endphp
<article class="{{ $cardContext === 'dashboard' ? 'hm-dash26-person' : 'hm-four-profile hm-matched-card' }} hm-unified-profile-card">
    <a href="{{ $detailUrl }}" class="{{ $cardContext === 'dashboard' ? 'hm-dash26-photo' : 'hm-four-photo' }}" aria-label="View {{ $person->first_name }} profile">
        <img src="{{ $photo }}" onerror="this.onerror=null;this.src='{{ $fallback }}'" loading="lazy" alt="{{ $person->first_name }} profile">
        <b class="hm-card-match">{{ $cardMatchPercentage }}% Match</b>
        @if($cardVerified)
            <span class="hm-card-verified"><i class="las la-check-circle" aria-hidden="true"></i> Verified</span>
        @endif
    </a>
    <div class="hm-card-body">
        <strong class="hm-card-name">{{ trim($person->first_name.' '.$person->last_name) }}</strong>
        <span class="hm-card-age-religion">{{ $cardAge ? $cardAge.' Years' : 'Age not provided' }}@if($cardReligion) · {{ $cardReligion }}@endif</span>
        <p class="hm-card-meta"><i class="las la-map-marker" aria-hidden="true"></i>{{ $cardAddress?->city?->name ?? 'Location not provided' }}</p>
        <p class="hm-card-meta"><i class="las la-briefcase" aria-hidden="true"></i>{{ $cardMember?->job_title ?? 'Profession not provided' }}</p>
        <p class="hm-card-meta"><i class="las la-graduation-cap" aria-hidden="true"></i>{{ $cardEducation ?? 'Education not provided' }}</p>
        @include('frontend.member.partials.match_actions', ['person' => $person, 'detailUrl' => $detailUrl, 'matchActions' => $matchActions])
    </div>
</article>
