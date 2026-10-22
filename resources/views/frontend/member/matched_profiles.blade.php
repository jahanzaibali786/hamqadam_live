@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
$canRefresh=!auth()->user()->match_refresh_updated_at || \Carbon\Carbon::parse(auth()->user()->match_refresh_updated_at)->addMinutes(30)->isPast();
@endphp
<div class="hm-dashboard hm-listing-page">
  <div class="hm-top"><div><div class="hm-eyebrow">Smart discovery</div><h1 class="hm-heading"><i class="las la-heart text-primary"></i> Matched Profiles</h1><p class="hm-subtitle">Explore people who align with your partner preferences.</p></div>
    @if($canRefresh)<a href="{{ route('match.refresh') }}" class="btn btn-outline-primary"><i class="las la-sync mr-1"></i> Refresh Matches</a>
    @else <button type="button" class="btn btn-outline-secondary" disabled title="Available 30 minutes after your last refresh"><i class="las la-clock mr-1"></i> Refresh on cooldown</button>@endif
  </div>
  <div class="hm-card">
    <div class="hm-card-header"><h2>Recommended For You</h2><span class="badge badge-soft-primary">{{ $matchedProfiles->total() }} profiles</span></div>
    <div class="hm-card-body">
      <div class="hm-profiles-grid">
        @forelse($matchedProfiles as $matchedProfile)
          @php
            $person=$matchedProfile->matchedUser?:$matchedProfile->user;
            if(!$person || (float)$matchedProfile->match_percentage<=0) continue;
            $member=$person->member;
            $allowed=!(get_setting('full_profile_show_according_to_membership')==1&&auth()->user()->membership==1);
            $link=$allowed?route('member_profile',$person->id):'javascript:void(0);';
            $fallback=($member?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png';
            $avatar=show_profile_picture($person)&&$person->photo?uploaded_asset($person->photo):static_asset($fallback);
          @endphp
          <article class="hm-profile-tile">
            <div class="hm-profile-cover"><a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless><img src="{{ $avatar }}" loading="lazy" alt="{{ $person->first_name }} {{ $person->last_name }}"></a><span class="hm-profile-match">{{ round($matchedProfile->match_percentage) }}% Match</span></div>
            <div class="hm-profile-body">
              <h2><a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless>{{ $person->first_name }} {{ $person->last_name }}</a></h2>
              <div class="hm-profile-location">@if($member?->birthday){{ \Carbon\Carbon::parse($member->birthday)->age }} yrs · @endif{{ $member?->job_title?:'Hamqadam Member' }}</div>
              <div class="hm-profile-info">{{ \Illuminate\Support\Str::limit((string)($matchedProfile->compatibility_explanation?:'Matches your partner preferences'),88) }}</div>
              <div class="hm-progress"><span style="width:{{ min(100,max(0,$matchedProfile->match_percentage)) }}%"></span></div>
              <div class="hm-profile-tile-actions mt-3"><a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless class="btn btn-primary btn-sm">View Profile</a><button type="button" onclick="express_interest({{ $person->id }})" id="interest_a_id_{{ $person->id }}" class="btn btn-outline-primary btn-sm" aria-label="Express interest in {{ $person->first_name }}"><i class="las la-heart"></i></button></div>
            </div>
          </article>
        @empty
          <div class="hm-empty"><h3 class="h5">No matches found yet</h3><p>Complete your preferences or explore the member directory.</p><a href="{{ route('member.listing') }}" class="btn btn-primary btn-sm">Explore Members</a></div>
        @endforelse
      </div>
    </div><div class="p-3">{{ $matchedProfiles->links() }}</div>
  </div>
</div>
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
