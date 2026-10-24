@php
  $cmsTeamMembers = \App\Support\HamqadamTeamContent::members();
@endphp
<section class="hqed-team-hero"><div class="hqed-wrap hqed-centered"><span class="hqed-eyebrow"><i class="las la-shield-alt"></i> {{ $data['hero']['eyebrow'] }}</span><h1>{{ $data['hero']['title'] }} <em>{{ $data['hero']['accent'] }}</em></h1><p>{{ $data['hero']['description'] }}</p><div class="hqed-pills">
@foreach($data['hero']['badges'] as $badge)<span>{{ $badge['text'] }}</span>@endforeach
</div></div></section>
<section class="hqed-filter-bar"><div class="hqed-wrap hqed-filter-inner"><div><h3>{{ $data['practice']['title'] }}</h3><p>{{ $data['practice']['description'] }}</p></div><div class="hqed-filters" role="group" aria-label="Filter advisors"><button class="is-active" type="button" data-group="All">{{ $data['practice']['all_label'] }}</button>
@foreach($data['practice']['filters'] as $filter)<button type="button" data-group="{{ $filter['key'] }}">{{ $filter['label'] }}</button>@endforeach
</div></div></section>
<section class="hqed-section"><div class="hqed-wrap"><div class="hqed-team-section-heading"><div><span class="hqed-eyebrow">{{ $data['practice']['eyebrow'] }}</span><h2>{{ $data['practice']['heading'] }}</h2></div><span class="hqed-team-status"><b>●</b> {{ $data['practice']['status'] }}</span></div><div class="hqed-grid3 hqed-team-grid">
@foreach($cmsTeamMembers as $member)<article class="hqed-card hqed-person" data-member-group="{{ $member['group'] }}"><div class="hqed-person-head"><div class="hqed-avatar">@if(!empty($member['image_id']))<img src="{{ uploaded_asset($member['image_id']) }}" alt="{{ $member['name'] }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;border-radius:50%">@else{{ $member['initials'] }}@endif</div><span class="hqed-tag">{{ $member['badge'] }}</span></div><h3>{{ $member['name'] }}</h3><strong class="hqed-role">{{ $member['role'] }}</strong><p>{{ $member['bio'] }}</p><div class="hqed-focus"><strong>{{ $data['practice']['title'] }}</strong><small>{{ $member['focus'] }}</small></div><div class="hqed-person-footer">
    <span><i class="las la-map-marker"></i> {{ $member['location'] }}</span>
    @if (!empty($member['action_link']))
        <a href="{{ \App\Support\PublicNavigation::link((string) $member['action_link']) }}">
            {{ $member['action_text'] ?: 'View Details' }}
            <i class="las la-arrow-right"></i>
        </a>
    @endif
</div></article>@endforeach
</div></div></section>
<section class="hqed-council"><div class="hqed-wrap hqed-council-grid"><div><span class="hqed-eyebrow">{{ $data['council']['eyebrow'] }}</span><h2>{{ $data['council']['title'] }}</h2><p>{{ $data['council']['description'] }}</p><div class="hqed-grid2 hqed-council-stats">
@foreach($data['council']['stats'] as $stat)<div class="hqed-card"><strong>{{ $stat['value'] }}</strong><small>{{ $stat['label'] }}</small></div>@endforeach
</div></div><div class="hqed-council-cards">
@foreach($data['council']['cards'] as $card)<article class="hqed-card"><span class="hqed-icon"><i class="las la-{{ $card['icon'] }}"></i></span><h3>{{ $card['title'] }}</h3><p>{{ $card['description'] }}</p><small class="hqed-footnote">{{ $card['footnote'] }}</small></article>@endforeach
</div></div></section>
<section class="hqed-section"><div class="hqed-wrap"><div class="hqed-consult"><div><span class="hqed-eyebrow">{{ $data['consult']['eyebrow'] }}</span><h2>{{ $data['consult']['title'] }}</h2><p>{{ $data['consult']['description'] }}</p><div class="hqed-consult-helpline"><strong>{{ $data['consult']['hotline_label'] }}: {{ $data['consult']['hotline'] }}</strong><small>{{ $data['consult']['availability'] }}</small></div></div><div class="hqed-card hqed-consult-form"><h3>{{ $data['consult']['form_heading'] }}</h3><p>{{ $data['consult']['form_hint'] }}</p>
@if(session('advisor_request_status'))<div class="hqed-notice" role="status">{{ session('advisor_request_status') }}</div>@endif
<form method="POST" action="{{ route('editorial.team.consult') }}">@csrf<div class="hqed-form-field"><label for="hqed-name">{{ $data['consult']['name_label'] }}</label><input required maxlength="120" name="name" id="hqed-name" value="{{ old('name') }}" autocomplete="name"></div><div class="hqed-form-field"><label for="hqed-email">{{ $data['consult']['email_label'] }}</label><input required type="email" name="email" id="hqed-email" value="{{ old('email') }}" autocomplete="email"></div><div class="hqed-form-columns"><div class="hqed-form-field"><label for="hqed-city">{{ $data['consult']['city_label'] }}</label><select name="city" id="hqed-city">
@foreach($data['consult']['cities'] as $c)<option value="{{ $c['value'] }}">{{ $c['label'] }}</option>@endforeach
</select></div><div class="hqed-form-field"><label for="hqed-advisor">{{ $data['consult']['advisor_label'] }}</label><select name="advisor" id="hqed-advisor">
@foreach($data['consult']['advisors'] as $a)<option value="{{ $a['value'] }}">{{ $a['label'] }}</option>@endforeach
</select></div></div><div class="hqed-form-field"><label for="hqed-phone">{{ $data['consult']['phone_label'] }}</label><input required maxlength="35" name="phone" type="tel" id="hqed-phone" value="{{ old('phone') }}" autocomplete="tel"></div>
@if($errors->any())<div class="hqed-notice">{{ $errors->first() }}</div>@endif
<button class="hqed-btn" type="submit">{{ $data['consult']['submit_label'] }}</button><small>{{ $data['consult']['confidentiality'] }}</small></form></div></div></div></section>
@include('frontend.editorial.sections.cta',['cta'=>$data['cta'],'variant'=>'simple'])
@section('script')
<script>document.querySelectorAll('.hqed-filters button').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.hqed-filters button').forEach(x=>x.classList.toggle('is-active',x===b));document.querySelectorAll('.hqed-person').forEach(x=>{x.hidden=b.dataset.group!=='All'&&x.dataset.memberGroup!==b.dataset.group;});}));</script>
@endsection