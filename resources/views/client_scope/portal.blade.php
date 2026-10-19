@extends('frontend.layouts.member_panel')
@section('panel_content')
<main class="container py-5" aria-labelledby="scope-title">
  <h1 id="scope-title" class="h3 mb-3">My Matches &amp; Engagement</h1>
  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  <div class="row mb-4">
    <div class="col-md-7"><div class="card"><div class="card-body">
      <h2 class="h5">Share my profile</h2>
      <label for="client-scope-profile-url">Your profile link</label>
      <div class="input-group"><input id="client-scope-profile-url" class="form-control" readonly value="{{ $link }}" aria-label="My profile link"><div class="input-group-append"><button type="button" class="btn btn-primary" id="client-scope-copy">Copy profile link</button></div></div>
      <p id="copy-feedback" class="small text-muted mt-2" role="status"></p>
    </div></div></div>
    <div class="col-md-5"><div class="card"><div class="card-body">
      <h2 class="h5">Your account tools</h2>
      <a class="btn btn-outline-primary mb-2" href="{{ route('client.scope.support') }}">Support tickets</a>
      <a class="btn btn-outline-primary mb-2" href="{{ route('client.scope.satisfaction') }}">Rate your experience</a>
      <a class="btn btn-outline-primary mb-2" href="{{ route('client.scope.notifications') }}">Notifications & preferences</a>
      <a class="btn btn-outline-primary mb-2" href="{{ route('blog') }}">Articles & advice</a>
      <a class="btn btn-outline-primary mb-2" href="{{ route('member.referrals.index') }}">Refer Friends &amp; Coin Rewards</a>
    </div></div></div>
  </div>
  <h2 class="h4">Suggested matches</h2>
  <p class="text-muted">"Got Match" records your confirmation; it does not imply an independently verified marriage.</p>
  <div class="row">
    @forelse($matches as $match)
      <div class="col-md-6 col-lg-4 mb-3"><article class="card h-100"><div class="card-body">
        <h3 class="h6">{{ $match->matchedUser?->name ?? 'Profile #'.$match->match_id }}</h3>
        <p>{{ is_numeric($match->match_percentage) ? round($match->match_percentage, 1).'%' : 'Score unavailable' }} compatibility</p>
        @if(in_array((int) $match->match_id, $confirmations, true))
          @if(in_array((int) $match->match_id, $mutualConfirmations, true))
            <span class="badge badge-success">Both members confirmed</span>
          @else
            <span class="badge badge-warning">Awaiting the other member</span>
          @endif
        @else
          <form action="{{ route('client.scope.got-match') }}" method="POST">@csrf
            <input type="hidden" name="matched_user_id" value="{{ $match->match_id }}">
            <button class="btn btn-sm btn-primary" type="submit">Got Match</button>
          </form>
        @endif
      </div></article></div>
    @empty <div class="col-12"><p>No matches available yet.</p></div>
    @endforelse
  </div>
  {{ $matches->links() }}
  @if($sponsors->isNotEmpty())
  <section aria-labelledby="sponsored-title" class="mt-4"><h2 id="sponsored-title" class="h4">Sponsored listings</h2><div class="row">
    @foreach($sponsors as $sponsor)
    <div class="col-md-4 mb-3"><article class="card h-100" data-sponsor-impression-url="{{ route('client.scope.sponsor.impression', $sponsor) }}"><div class="card-body">
      <span class="badge badge-info mb-2">Sponsored</span>
      <h3 class="h6">{{ $sponsor->title }}</h3>
      @if($sponsor->sponsor_name)<p>{{ $sponsor->sponsor_name }}</p>@endif
      <a href="{{ route('client.scope.sponsor.visit',$sponsor) }}" class="btn btn-outline-primary btn-sm">View sponsored listing</a>
    </div></article></div>
    @endforeach
  </div></section>
  @endif
</main>
@endsection
@section('script')
<script>
document.getElementById('client-scope-copy')?.addEventListener('click', function () {
  var el=document.getElementById('client-scope-profile-url');
  var status=document.getElementById('copy-feedback');
  if(navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(el.value).then(function(){status.textContent='Copied.';},function(){status.textContent='Please select and copy the URL.';});
  else { el.select(); status.textContent='Selected. Press Ctrl+C to copy.'; }
});
// Impression events are sent only after the sponsored card actually enters
// the viewport. Hidden/offscreen cards are not counted as impressions.
if ('IntersectionObserver' in window) {
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);
      fetch(entry.target.getAttribute('data-sponsor-impression-url'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
      }).catch(function () { /* display is unaffected if analytics fails */ });
    });
  }, {threshold: 0.5});
  document.querySelectorAll('[data-sponsor-impression-url]').forEach(function(el) { observer.observe(el); });
}
</script>
@endsection
