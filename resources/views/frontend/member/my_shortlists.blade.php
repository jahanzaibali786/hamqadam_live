@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
  $shortlistItems = $shortlists->getCollection()->filter(fn ($entry) => $entry->user !== null);
  $shortlistIds = $shortlistItems->pluck('user_id')->all();
  $scores = \App\Models\ProfileMatch::query()->where('user_id', auth()->id())->whereIn('match_id', $shortlistIds)->pluck('match_percentage', 'match_id');
  $cities = \App\Models\Address::query()->with('city')->whereIn('user_id', $shortlistIds)->where('type', 'present')->get()->keyBy('user_id');
  $viewAllowed = !(get_setting('full_profile_show_according_to_membership') == 1 && auth()->user()->membership == 1);
@endphp
<div class="hm-shortlist-2026" id="hm-shortlist" data-count="{{ $shortlists->total() }}">
  <header class="hm-sl-hero">
    <div class="hm-sl-hero-copy"><span class="hm-sl-title-icon"><i class="las la-bookmark"></i></span><div><h1>Shortlist</h1><p>Keep your favorite profiles here and never lose them.</p></div></div>
    <div class="hm-sl-hero-quote"><i class="las la-heart"></i><strong>Save the profiles you like<br>and connect at the right time.</strong></div>
  </header>
  <div class="hm-sl-toolbar">
    <div class="hm-sl-tabs" role="group" aria-label="Shortlist filters">
      <button class="is-active" type="button" data-sl-tab="all">All Shortlisted ({{ $shortlists->total() }})</button>
      <button type="button" data-sl-tab="recent">Recently Added</button>
      <button type="button" data-sl-tab="compatibility">Highest Compatibility</button>
      <button type="button" data-sl-tab="online">Online Now</button>
    </div>
    <div class="hm-sl-search"><i class="las la-search"></i><input id="hm-sl-search" type="search" placeholder="Search in shortlist..." aria-label="Search in shortlist"></div>
    <label class="hm-sl-sort"><i class="las la-sort-amount-down"></i><span>Sort by</span><select id="hm-sl-sort" aria-label="Sort shortlist"><option value="recent">Recently Added</option><option value="compatibility">Compatibility</option><option value="age">Age</option><option value="name">Name</option></select></label>
  </div>
  <div class="hm-sl-layout">
    <section class="hm-sl-main" aria-label="Saved member profiles">
      <div class="hm-sl-grid" id="hm-sl-grid">
        @foreach($shortlistItems as $shortlist)
          @php
            $person=$shortlist->user;
            $profile=$person->member;
            $age=$profile?->birthday ? \Carbon\Carbon::parse($profile->birthday)->age : null;
            $score=(float)($scores[$person->id] ?? 0);
            $city=$cities[$person->id]?->city?->name ?? '';
            $career=$person->career?->first()?->designation ?? $profile?->job_title ?? '';
            $religion=$person->spiritual_backgrounds?->religion?->name ?? '';
            $online=\Illuminate\Support\Facades\Cache::has('user-is-online-'.$person->id);
            $fallback=static_asset(($profile?->gender ?? 1) == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
            $avatar=$person->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : $fallback;
          @endphp
          <article class="hm-sl-card" id="shortlisted_member_{{ $person->id }}" data-name="{{ strtolower(trim($person->first_name.' '.$person->last_name)) }}" data-age="{{ $age ?? 0 }}" data-score="{{ round($score) }}" data-city="{{ strtolower($city) }}" data-career="{{ strtolower($career) }}" data-education="{{ strtolower($person->education?->first()?->degree ?? '') }}" data-online="{{ $online?1:0 }}" data-recent="{{ $shortlist->created_at?->timestamp ?? 0 }}">
            <div class="hm-sl-image">
              <img src="{{ $avatar }}" alt="{{ $person->first_name }} {{ $person->last_name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'">
              <span class="hm-sl-online {{ $online?'is-online':'' }}" title="{{ $online?'Online':'Offline' }}"></span>
              <button type="button" class="hm-sl-favorite" onclick="remove_shortlist({{ $person->id }})" aria-label="Remove {{ $person->first_name }} from shortlist"><i class="las la-heart"></i></button>
              @if($score > 0)<span class="hm-sl-score">{{ round($score) }}% Match</span>@endif
            </div>
            <div class="hm-sl-card-body">
              <h2>{{ $person->first_name }} {{ $person->last_name }}</h2>
              <p class="hm-sl-muted">{{ $age ? $age.' · ' : '' }}{{ $city ?: 'Location not provided' }}</p>
              <p class="hm-sl-detail"><i class="las la-graduation-cap"></i>{{ $career ?: 'Profession not provided' }}</p>
              <p class="hm-sl-detail"><i class="las la-moon"></i>{{ $religion ?: 'Religion not provided' }}</p>
              <p class="hm-sl-detail"><i class="las la-map-marker"></i>{{ $city ?: 'City not provided' }}</p>
              <div class="hm-sl-card-actions">
                @if($viewAllowed)<a href="{{ route('member_profile', $person->id) }}" class="hm-sl-view">View Profile</a>
                @else<button type="button" class="hm-sl-view" onclick="package_update_alert()">View Profile</button>@endif
                <button type="button" class="hm-sl-more" onclick="express_interest({{ $person->id }})" id="interest_a_id_{{ $person->id }}" title="Express interest (1 coin)" aria-label="Express interest in {{ $person->first_name }}"><i class="las la-ellipsis-v"></i></button>
              </div>
            </div>
          </article>
        @endforeach
        <div class="hm-sl-add" id="hm-sl-add"><i class="lar la-bookmark"></i><h2>Add More Profiles</h2><p>Keep exploring and save profiles you like.</p><a href="{{ route('member.listing') }}">Find More Matches <i class="las la-arrow-right"></i></a></div>
      </div>
      <div class="hm-sl-no-results" id="hm-sl-no-results" hidden>No profiles match these filters. <button type="button" id="hm-sl-clear">Clear filters</button></div>
      <div class="hm-sl-pagination">{{ $shortlists->links() }}</div>
    </section>
    <aside class="hm-sl-filters"><div class="hm-sl-filter-heading"><strong><i class="las la-filter"></i> Filters</strong><button type="button" id="hm-sl-reset">Reset</button></div>
      <div class="hm-sl-field"><label for="hm-sl-min">Age Range</label><div class="hm-sl-range"><input type="number" id="hm-sl-min" min="18" max="100" value="20" aria-label="Minimum age"><span>to</span><input type="number" id="hm-sl-max" min="18" max="100" value="45" aria-label="Maximum age"></div></div>
      <div class="hm-sl-field"><label for="hm-sl-city">City</label><select id="hm-sl-city"><option value="">All Cities</option>@foreach($cities->map(fn($x)=>$x->city?->name)->filter()->unique()->sort() as $cityName)<option value="{{ strtolower($cityName) }}">{{ $cityName }}</option>@endforeach</select></div>
      <div class="hm-sl-field"><label for="hm-sl-education">Education</label><input id="hm-sl-education" type="search" placeholder="All education levels"></div>
      <div class="hm-sl-field"><label for="hm-sl-career">Profession</label><input id="hm-sl-career" type="search" placeholder="All professions"></div>
      <div class="hm-sl-field"><label for="hm-sl-presence">Online Status</label><select id="hm-sl-presence"><option value="">All</option><option value="1">Online Now</option><option value="0">Offline</option></select></div>
      <button type="button" id="hm-sl-apply" class="hm-sl-apply"><i class="las la-filter"></i> Apply Filters</button>
    </aside>
  </div>
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
