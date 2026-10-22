@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-dashboard hm-listing-page">
  <div class="hm-top"><div><div class="hm-eyebrow">Profiles you saved</div><h1 class="hm-heading"><i class="las la-bookmark text-primary"></i> My Shortlist</h1><p class="hm-subtitle">Keep your favorite member profiles in one place and connect at the right time.</p></div><a href="{{ route('member.listing') }}" class="btn btn-primary"><i class="las la-user-plus mr-1"></i> Find More Matches</a></div>
  <div class="hm-parity-listing-layout"><main>
  <div class="hm-card mb-4">
    <div class="hm-card-header"><h2>All Shortlisted Profiles <span class="badge badge-soft-primary">{{ $shortlists->total() }}</span></h2><span class="hm-subtitle">Manage your shortlist</span></div>
    <div class="hm-card-body">
      <div class="hm-profiles-grid">
        @forelse($shortlists as $shortlist)
          @php
           $person=$shortlist->user;
           if(!$person) continue;
           $profile=$person->member;
           $avatar=$person->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : static_asset(($profile?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png');
           $score=\App\Models\ProfileMatch::query()->where('user_id',auth()->id())->where('match_id',$person->id)->value('match_percentage');
           $allowed=!(get_setting('full_profile_show_according_to_membership')==1 && auth()->user()->membership==1);
           $link=$allowed?route('member_profile',$person->id):'javascript:void(0);';
          @endphp
          <article class="hm-profile-tile" id="shortlisted_member_{{ $shortlist->user_id }}">
            <div class="hm-profile-cover">
              <a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless><img src="{{ $avatar }}" loading="lazy" alt="{{ $person->first_name }} {{ $person->last_name }}"></a>
              <span class="hm-profile-heart"><i class="las la-heart"></i></span>
              @if($score>0)<span class="hm-profile-match">{{ round($score) }}% match</span>@endif
            </div>
            <div class="hm-profile-body">
              <h2><a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless>{{ $person->first_name }} {{ $person->last_name }}</a></h2>
              <div class="hm-profile-location">@if($profile?->birthday){{ \Carbon\Carbon::parse($profile->birthday)->age }} yrs · @endif{{ $profile?->job_title ?: 'Hamqadam member' }}</div>
              <div class="hm-profile-info"><i class="las la-user-check"></i> {{ $person->approved?'Profile approved':'Profile under review' }}</div>
              <div class="hm-profile-tile-actions">
                <a href="{{ $link }}" @unless($allowed) onclick="package_update_alert()" @endunless class="btn btn-primary btn-sm">View Profile</a>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="remove_shortlist({{ $shortlist->user_id }})" title="Remove from shortlist" aria-label="Remove {{ $person->first_name }} from shortlist"><i class="las la-trash-alt"></i></button>
              </div>
              <div class="mt-2"><button type="button" onclick="express_interest({{ $shortlist->user_id }})" id="interest_a_id_{{ $shortlist->user_id }}" class="btn btn-soft-primary btn-sm btn-block"><i class="las la-heart mr-1"></i> Express Interest</button></div>
            </div>
          </article>
        @empty
          <div class="hm-empty"><i class="las la-bookmark" style="font-size:38px;color:#e3317c"></i><h3 class="h5 mt-2">Your shortlist is empty</h3><p>Discover compatible members and save profiles you like.</p><a href="{{ route('member.listing') }}" class="btn btn-primary">Explore Members</a></div>
        @endforelse
      </div>
    </div>
    <div class="p-3">{{ $shortlists->links() }}</div>
  </div>
</main><aside class="hm-parity-listing-aside">
<section class="hm-card"><header class="hm-card-header"><h2>Shortlist Overview</h2></header><div class="hm-card-body"><div class="hm-parity-listing-insight"><i class="las la-heart"></i><strong>{{ $shortlists->total() }}</strong><span>Saved Profiles</span></div><p class="small text-muted mt-3 mb-0">These profiles are based on your current account and preferences, not demonstration values.</p></div></section>
<section class="hm-card"><header class="hm-card-header"><h2>Discovery Options</h2></header><div class="hm-card-body"><p class="small text-muted">Use advanced discovery to refine age, location, education and partner preferences.</p><a class="btn btn-primary btn-block" href="{{ route('member.listing') }}">Discover More Members</a><a class="btn btn-outline-primary btn-block mt-2" href="{{ route('profile_settings') }}">Update Preferences</a></div></section>
<section class="hm-ref-help"><i class="las la-shield-alt"></i><b>Safe Connections</b><p>Use the existing profile viewer to keep account entitlements and access permissions intact.</p></section>
</aside></div>
</div>
@endsection

@section('modal')
  @include('modals.confirm_modal')
  @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function remove_shortlist(id) {
      $.post('{{ route('member.remove_from_shortlist') }}',
        {
          _token: '{{ csrf_token() }}',
          id: id
        },
        function (data) {
          if (data == 1) {
            $("#shortlisted_member_"+id).hide();
            AIZ.plugins.notify('success', '{{translate('You Have Removed This Member From Shortlist')}}');
          }
          else {
            AIZ.plugins.notify('danger', '{{translate('Something went wrong')}}');
          }
        }
      );
    }

    // Express Interest
    var package_validity = {{ package_validity(Auth::user()->id) }};

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
