@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-four">
  <header class="hm-four-head"><div><h1>My Interests</h1><p>Manage your interest requests and genuine connections.</p></div></header>
  <div class="hm-four-layout"><main>
  <nav class="hm-four-tabs">
  @foreach ([['received','Received'],['sent','Sent'],['accepted','Accepted'],['rejected','Rejected']] as $item)
    <a class="{{ $tab === $item[0] ? 'active' : '' }}" href="{{ route('my_interests.index', ['tab' => $item[0]]) }}">{{ $item[1] }} ({{ $counts[$item[0]] ?? 0 }})</a>
  @endforeach
  </nav>
  <div class="hm-four-rows">
  @forelse ($interests as $interest)
    @php
      $isSender = (int) $interest->interested_by === (int) $subjectUserId;
      $person = $isSender ? $interest->recipient : $interest->sender;
      $state = $interest->status instanceof \BackedEnum ? (int)$interest->status->value : (int)$interest->status;
    @endphp
    @if ($person)
      @php
        $fallback = static_asset(($person->member?->gender ?? 1) == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
        $photo = $person->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : $fallback;
        $city = $person->addresses->firstWhere('type','present')?->city?->name ?? 'Location unavailable';
      @endphp
      <article class="hm-four-request">
        <img src="{{ $photo }}" alt="{{ $person->first_name }}" onerror="this.onerror=null;this.src='{{ $fallback }}'">
        <div><h2>{{ $person->first_name }} {{ $person->last_name }}</h2><p>{{ $city }}</p><small>{{ $person->member?->job_title ?? 'Member' }}</small></div>
        <blockquote>{{ $interest->initial_note ?: 'Interest sent through Hamqadam.' }}</blockquote>
        <div class="hm-four-request-actions">
          <small>{{ $interest->created_at?->diffForHumans() }}</small>
          <a class="hm-four-primary" href="{{ route('member_profile', $person->id) }}">View Details</a>
          @if ($state === 0 && (int)$interest->user_id === (int)$subjectUserId)
            <button type="button" onclick="accept_interest({{ $interest->id }})">Accept</button>
            <button type="button" class="outlined" onclick="reject_interest({{ $interest->id }})">Decline</button>
          @else
            <span class="hm-four-state">{{ $state === 1 ? 'Accepted' : ($state === 2 ? 'Declined' : 'Pending') }}</span>
          @endif
        </div>
      </article>
    @endif
  @empty
    <p class="hm-four-empty">No my interests in this category.</p>
  @endforelse
  </div>
  {{ $interests->links() }}
  </main>
  <aside><section class="hm-four-panel"><h2>My Interests Overview</h2><div class="hm-four-metrics">
  @foreach ($counts as $key => $value)
    <span><b>{{ $value }}</b><small>{{ ucfirst(str_replace('_',' ',$key)) }}</small></span>
  @endforeach
  </div></section><section class="hm-four-promo"><a href="{{ route('member.reference.packages') }}">View Premium Plans</a></section></aside>
  </div>
</div>
@endsection
@section('modal')
<div class="modal fade interest_accept_modal"><div class="modal-dialog modal-sm"><div class="modal-content p-3"><h4>Accept Interest?</h4><form method="POST" action="{{ route('accept_interest') }}">@csrf<input type="hidden" id="interest_accept_id" name="interest_id"><button type="submit" class="btn btn-primary">Confirm</button></form></div></div></div>
<div class="modal fade interest_reject_modal"><div class="modal-dialog modal-sm"><div class="modal-content p-3"><h4>Decline Interest?</h4><form method="POST" action="{{ route('reject_interest') }}">@csrf<input type="hidden" id="interest_reject_id" name="interest_id"><button type="submit" class="btn btn-danger">Confirm</button></form></div></div></div>
@endsection
@section('script')
<script>function accept_interest(id){document.getElementById('interest_accept_id').value=id;$('.interest_accept_modal').modal('show')}function reject_interest(id){document.getElementById('interest_reject_id').value=id;$('.interest_reject_modal').modal('show')}</script>
@endsection