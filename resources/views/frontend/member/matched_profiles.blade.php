@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-four"><header class="hm-four-head"><div><h1><i class="las la-user-friends"></i> Matched Profiles</h1><p>People who are a great match based on your profile and preferences.</p></div><aside>Better matches lead to brighter futures.</aside></header>
<div class="hm-four-layout"><main>
<nav class="hm-four-tabs"><a class="{{ !request('tab')||request('tab')==='all'?'active':'' }}" href="{{ route('my_matched_profiles') }}">All Matches ({{ $matchedProfiles->total() }})</a><a class="{{ request('tab')==='high'?'active':'' }}" href="{{ route('my_matched_profiles',['tab'=>'high']) }}">High Compatibility</a><a class="{{ request('tab')==='new'?'active':'' }}" href="{{ route('my_matched_profiles',['tab'=>'new']) }}">New Matches</a><a class="{{ request('tab')==='recent'?'active':'' }}" href="{{ route('my_matched_profiles',['tab'=>'recent']) }}">Recently Active</a></nav>
<div class="hm-four-matches">@forelse($matchedProfiles as $match)@php($person=$match->matchedUser)@if($person)@php($member=$person->member)@php($fallback=static_asset(($member?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png'))@php($allowed=get_setting('full_profile_show_according_to_membership')!=1||auth()->user()->membership!=1)<article class="hm-four-profile"><div class="hm-four-photo"><img src="{{ $person->photo&&show_profile_picture($person)?uploaded_asset($person->photo):$fallback }}" alt="{{ $person->first_name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'"><b>♥ {{ round($match->match_percentage) }}% Match</b></div><div><h2>{{ $person->first_name }} {{ $person->last_name }}@if($member?->birthday), {{ \Carbon\Carbon::parse($member->birthday)->age }}@endif</h2><p><i class="las la-map-marker"></i> {{ $person->addresses->firstWhere('type','present')?->city?->name??'Location not provided' }}</p><p><i class="las la-briefcase"></i> {{ $member?->job_title??'Profession not provided' }}</p><p><i class="las la-graduation-cap"></i> {{ $person->education->last()?->degree?:'Education not provided' }}</p><div class="hm-four-actions"><a href="{{ $allowed?route('member_profile',$person->id):route('packages') }}">View Profile</a><button type="button" id="interest_a_id_{{ $person->id }}" onclick="express_interest({{ $person->id }})" title="Send Interest"><i class="las la-heart"></i></button></div></div></article>@endif@empty<p class="hm-four-empty">No compatible profiles match these filters.</p>@endforelse</div>{{ $matchedProfiles->links() }}</main>
<aside><section class="hm-four-panel"><h2>Match Insights</h2><div class="hm-four-insight">♥ <strong>{{ $matchedProfiles->total() }}</strong><small>Matching profiles in current filter</small></div><a href="{{ route('client.scope.portal') }}">View Compatibility Analysis →</a></section><section class="hm-four-panel"><h2>Filters</h2><form action="{{ route('my_matched_profiles') }}" method="GET"><input type="hidden" name="tab" value="{{ request('tab','all') }}"><label>Search Matches<input name="search" placeholder="Name" value="{{ request('search') }}"></label><label>Minimum Age<input type="number" name="min_age" min="18" max="90" value="{{ request('min_age',20) }}"></label><label>Maximum Age<input type="number" name="max_age" min="18" max="90" value="{{ request('max_age',35) }}"></label><label class="hm-four-check"><input type="checkbox" name="verified" value="1" @checked(request('verified'))> Verified Profiles Only</label><button>Apply Filters</button></form></section><section class="hm-four-promo"><a href="{{ route('member.reference.packages') }}">Upgrade to Premium →</a></section></aside></div></div>
@endsection
@section('modal')
  @include('modals.confirm_modal')
  @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">

  //package Validity
  var package_validity = false;
  @if(package_validity(Auth::user()->id))
      package_validity = true;
  @endif

  function express_interest(id)
  {
    var user_id = {{ Auth::user()->id }}
    $.post('{{ route('user.remaining_package_value') }}', {_token:'{{ csrf_token() }}', id:user_id, colmn_name:'remaining_interest' }, function(data){
        var remaining_interest = data;
        if(!package_validity || remaining_interest < 1){
            $('.package_update_alert_modal').modal('show');
        }
        else{
          $('.confirm_modal').modal('show');
          $("#confirm_modal_title").html("{{ translate('Confirm Express Interest') }}");
          $("#confirm_modal_content").html("<p class='fs-14'>{{translate('Coin Balance')}}: "+remaining_interest+" {{translate('Coins')}}</p><small class='text-danger fs-12'>{{translate('**N.B. Expressing an interest will cost 1 coin**')}}</small>");
          $("#confirm_button").attr("onclick","do_express_interest("+id+")");
        }
    });
  }

  function do_express_interest(id){
    $('.confirm_modal').modal('hide');
    $("#interest_a_id_"+id).removeAttr("onclick");
    $.post('{{ route('express-interest.store') }}',
      {
        _token: '{{ csrf_token() }}',
        id: id
      },
      function (data) {
        if (data) {
          $("#interest_a_id_"+id).attr("class","btn btn-soft-success btn-icon btn-circle btn-sm");
          $("#interest_a_id_"+id).attr("title","{{ translate('Interest Expressed') }}");
          AIZ.plugins.notify('success', '{{translate('Interest Expressed Sucessfully')}}');
        }
        else {
            AIZ.plugins.notify('danger', '{{translate('Something went wrong')}}');
        }
      }
    );
  }

  function package_update_alert(){
    $('.package_update_alert_modal').modal('show');
  }

</script>
@endsection
