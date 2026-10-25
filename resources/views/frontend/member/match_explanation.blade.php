@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $person=$match->matchedUser;
 $score=max(0,min(100,(int)round($match->match_percentage)));
 $criteria=(array)($match->compatibility_reasons??[]);
 $concerns=(array)($match->compatibility_concerns??[]);
 $breakdown=(array)($match->score_breakdown??[]);
@endphp
<div class="hm-four hm-match-reasons">
<header class="hm-four-head"><div><a href="{{ route('my_matched_profiles') }}"><i class="las la-arrow-left"></i> Matched Profiles</a><h1>Why This Match?</h1><p>Compatibility insights for {{ trim((string)$person?->first_name.' '.(string)$person?->last_name) }} based on your preferences.</p></div></header>
<div class="hm-ref26-card p-4 mb-3"><h2>{{ $score }}% Compatibility</h2><div class="progress my-3"><div class="progress-bar bg-primary" style="width:{{ $score }}%"></div></div><p>{{ $match->compatibility_explanation ?: 'This match is based on your completed profile and preferences.' }}</p></div>
<div class="row"><div class="col-md-6 mb-3"><section class="hm-ref26-card p-4 h-100"><h3>Shared Preferences</h3>@forelse($criteria as $criterion)<p><i class="las la-check-circle text-success"></i> {{ is_scalar($criterion)?$criterion:json_encode($criterion) }}</p>@empty<p>Detailed shared factors are not yet calculated.</p>@endforelse</section></div>
<div class="col-md-6 mb-3"><section class="hm-ref26-card p-4 h-100"><h3>Considerations</h3>@forelse($concerns as $concern)<p><i class="las la-info-circle text-primary"></i> {{ is_scalar($concern)?$concern:json_encode($concern) }}</p>@empty<p>No additional concerns have been recorded.</p>@endforelse</section></div></div>
@if(count($breakdown))<section class="hm-ref26-card p-4 mb-3"><h3>Compatibility Breakdown</h3>@foreach($breakdown as $label=>$value)<div class="d-flex justify-content-between py-2 border-bottom"><span>{{ ucwords(str_replace('_',' ',(string)$label)) }}</span><strong>{{ is_scalar($value)?$value:json_encode($value) }}</strong></div>@endforeach</section>@endif
<div class="text-right mb-4"><a href="{{ route('member_profile',$person->id) }}" class="btn btn-primary">View Profile</a></div></div>
@endsection