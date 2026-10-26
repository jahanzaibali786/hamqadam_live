@php($homeTeam = \App\Support\HamqadamTeamContent::members())
@if(count($homeTeam))
<section class="hq-home-team" aria-labelledby="hq-home-team-title" data-home-team>
<div class="container"><div class="hq-home-team-heading"><span>{{ \App\Support\HomePageContent::get('team_kicker') }}</span><h2 id="hq-home-team-title">{{ \App\Support\HomePageContent::get('team_title') }}</h2><p>{{ \App\Support\HomePageContent::get('team_description') }}</p></div>
<div class="hq-home-team-controls"><button type="button" data-home-team-prev aria-label="Previous team member"><i class="las la-arrow-left"></i></button><button type="button" data-home-team-next aria-label="Next team member"><i class="las la-arrow-right"></i></button></div>
<div class="hq-home-team-track" data-home-team-track tabindex="0">
@foreach($homeTeam as $member)
<article class="hq-home-team-card">
<div class="hq-home-team-avatar">@if(!empty($member['image_id']))<img src="{{ uploaded_asset($member['image_id']) }}" alt="{{ $member['name'] }}" loading="lazy">@else<span>{{ $member['initials'] ?: mb_strtoupper(mb_substr($member['name'],0,2)) }}</span>@endif</div>
<span class="hq-home-team-badge">{{ $member['badge'] }}</span><h3>{{ $member['name'] }}</h3><strong>{{ $member['role'] }}</strong><p>{{ \Illuminate\Support\Str::limit($member['bio'],135) }}</p><small>{{ $member['location'] }}</small>
</article>
@endforeach
</div>
<div class="hq-home-team-footer"><a href="{{ route('editorial.team') }}">{{ \App\Support\HomePageContent::get('team_button') }} <i class="las la-arrow-right"></i></a></div></div></section>
@endif
