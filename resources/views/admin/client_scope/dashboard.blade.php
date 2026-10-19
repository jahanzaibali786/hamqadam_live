@extends('admin.layouts.app')
@section('content')
@include('admin.client_scope.partials.completion_insights')
@include('admin.client_scope.partials.observed_retention')
<div class="aiz-titlebar text-left mt-2 mb-3"><h1 class="h3">Hamqadam Client Scope — Admin Monitoring</h1><p class="text-muted">All figures use recorded database events. Missing instrumentation is reported as zero observed events, not proof of inactivity.</p></div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p><a class="btn btn-outline-primary" href="{{ route('admin.client.scope.profiles') }}">Advanced member profile search</a></p>
<div class="row">
 <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><h2 class="h6">Total recorded matches</h2><strong class="h3">{{ number_format($report['total_matches']) }}</strong></div></div></div>
 <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><h2 class="h6">Got Match confirmations</h2><strong class="h3">{{ number_format($report['got_match_confirmations']) }}</strong><small class="d-block">User-reported, not independently confirmed</small></div></div></div>
 <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><h2 class="h6">NPS</h2><strong class="h3">{{ $report['nps']['score'] === null ? 'No data' : $report['nps']['score'] }}</strong><small class="d-block">{{ $report['nps']['responses'] }} responses</small></div></div></div>
 <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><h2 class="h6">Support ratings</h2><strong class="h3">{{ $report['support_average'] ?? 'No data' }}</strong><small class="d-block">{{ $report['support_responses'] }} rated tickets</small></div></div></div>
</div>
<section class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Linked-user journey (90-day signup cohort)</h2></div><div class="card-body table-responsive"><p class="text-muted small">Each stage counts only the same registered people who had evidence for every prior stage. Uninstrumented events may undercount activity.</p><table class="table table-sm"><thead><tr><th>Stage</th><th>People</th><th>Conversion</th><th>Drop-off</th></tr></thead><tbody>@forelse(($report['linked_funnel'] ?? []) as $stage)<tr><td>{{ $stage['name'] }}</td><td>{{ $stage['users'] }}</td><td>{{ $stage['conversion_pct'] === null ? '—' : $stage['conversion_pct'].'%' }}</td><td>{{ $stage['dropoff'] }}</td></tr>@empty<tr><td colspan="4">Analytics events not available</td></tr>@endforelse</tbody></table></div></section>
<div class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">NPS monthly trend</h2></div><div class="card-body table-responsive">
<p class="small text-muted">Only actual completed satisfaction surveys are counted. No response is shown as “—”, not a zero score.</p>
<table class="table table-sm"><thead><tr><th>Month</th><th>Responses</th><th>NPS</th></tr></thead><tbody>
@foreach($report['nps_trend'] as $month)<tr><td>{{ $month['month'] }}</td><td>{{ $month['responses'] }}</td><td>{{ $month['score'] ?? '—' }}</td></tr>@endforeach
</tbody></table></div></div>
<div class="row">
<div class="col-lg-7 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Journey Funnel — observed stages</h2></div><div class="card-body table-responsive">
<p class="small text-muted">These are independent populations, not a linked-cohort conversion funnel. Percentages can exceed 100% until cross-stage event tracking is complete.</p>
<table class="table"><thead><tr><th>Stage</th><th>Users</th><th>Prior stage %</th><th>Evidence</th></tr></thead><tbody>
@foreach($report['funnel'] as $step)<tr><td>{{ $step['label'] }}</td><td>{{ $step['users'] }}</td><td>{{ $step['relative_to_previous_pct'] === null ? '—' : $step['relative_to_previous_pct'].'%' }}</td><td><small>{{ $step['source'] }}</small></td></tr>@endforeach
</tbody></table></div></div></div>
<div class="col-lg-5 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Observed engagement cohorts</h2></div><div class="card-body table-responsive"><p class="small text-muted">Observed activity during the latest 30 days by registration month; this is not day-7/day-30 retention.</p><table class="table"><thead><tr><th>Signup month</th><th>Users</th><th>Active last 30d</th></tr></thead><tbody>
@foreach($report['cohorts'] as $cohort)<tr><td>{{ $cohort['month'] }}</td><td>{{ $cohort['signups'] }}</td><td>{{ $cohort['active_last_30d'] }}</td></tr>@endforeach
</tbody></table></div></div></div>
</div>
<div class="row">
<div class="col-md-6 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Feature adoption (instrumented only)</h2></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>Feature</th><th>Uses</th><th>Users</th></tr></thead><tbody>
@forelse($report['adoption'] as $a)<tr><td>{{ $a->feature_key }}</td><td>{{ $a->uses }}</td><td>{{ $a->people }}</td></tr>@empty<tr><td colspan="3">No events recorded</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="col-md-6 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Geographic distribution (profiles with saved city)</h2></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>City</th><th>Unique profiles</th></tr></thead><tbody>
@forelse($report['geography'] as $place)<tr><td>{{ $place->location }}</td><td>{{ $place->users }}</td></tr>@empty<tr><td colspan="2">No saved city data available</td></tr>@endforelse
</tbody></table></div></div></div>
</div>
<div class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Sponsored Listings &amp; Ad Gating</h2></div><div class="card-body">
<form class="mb-4" method="POST" action="{{ route('admin.client.scope.options') }}">@csrf<label><input type="checkbox" name="show_ads_to_paid" value="1" @checked($paidAds)> Show sponsored listings to paid members (default: off)</label><button class="btn btn-primary btn-sm ml-2">Save rule</button></form>
<h3 class="h6">Create listing</h3>
<form method="POST" action="{{ route('admin.client.scope.sponsors.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Title</label><input class="form-control" name="title" required maxlength="150"></div>
<div class="col-md-4 form-group"><label>Sponsor</label><input class="form-control" name="sponsor_name" maxlength="120"></div>
<div class="col-md-4 form-group"><label>Destination (HTTPS preferred)</label><input class="form-control" name="target_url" type="url" placeholder="https://..."></div>
<div class="col-md-3 form-group"><label>Placement</label><select class="form-control" name="placement"><option value="discovery">Discovery</option><option value="home">Home</option><option value="match">Match</option></select></div>
<div class="col-md-2 form-group"><label>Priority</label><input class="form-control" type="number" name="priority" value="0" min="0" max="100" required></div>
<div class="col-md-3 form-group"><label>Start at (optional)</label><input class="form-control" type="datetime-local" name="starts_at"></div>
<div class="col-md-3 form-group"><label>End at (optional)</label><input class="form-control" type="datetime-local" name="ends_at"></div>
<div class="col-md-1 form-group"><label>Active</label><div><input type="checkbox" name="active" value="1" checked></div></div>
</div>
<button class="btn btn-primary">Create sponsored listing</button></form>
<div class="table-responsive mt-4"><table class="table"><thead><tr><th>Title</th><th>Placement</th><th>Active</th><th>Clicks</th><th>Impressions*</th><th>Disable</th></tr></thead><tbody>
@forelse($sponsors as $sponsor)<tr><td>{{ $sponsor->title }}<small class="d-block">{{ $sponsor->sponsor_name }}</small></td><td>{{ $sponsor->placement }}</td><td>{{ $sponsor->active ? 'Yes' : 'No' }}</td><td>{{ $sponsor->clicks }}</td><td>{{ $sponsor->impressions }}</td><td>
@if($sponsor->active)<form method="POST" action="{{ route('admin.client.scope.sponsors.disable',$sponsor) }}">@csrf @method('DELETE')<button class="btn btn-soft-danger btn-sm">Disable</button></form>@endif</td></tr>@empty<tr><td colspan="6">No sponsored listings</td></tr>@endforelse
</tbody></table></div><small>*Impressions are tracked after a sponsored card enters the member viewport (limited to one count per member per hour).</small>
{{ $sponsors->links() }}
</div></div>
<div class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Notification delivery status</h2></div><div class="card-body"><p>Statuses from recorded delivery logs (not guaranteed provider delivery acknowledgements).</p>
@forelse($notificationStatus as $status => $count)<span class="badge badge-info mr-2">{{ ucfirst($status) }}: {{ $count }}</span>@empty<p>No notification delivery logs.</p>@endforelse
</div></div>
<div class="row"><div class="col-lg-5 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Gamification — Got Match reward rule</h2></div><div class="card-body">
<p class="small text-muted">Coin reward is issued at most once for each server-verified user confirmation and subject to the per-user limit. Other achievement types remain out of scope of this rule.</p>
<section class="border rounded p-3 mb-3"><h3 class="h6">Verified milestone rewards</h3>
@foreach(['profile_completed'=>'Verified completed profile','first_interest'=>'First real interest sent'] as $event => $title)
@php($configuredRule = $rewardRules->firstWhere('event_key', $event))
<form method="POST" action="{{ route('admin.client.scope.rewards.rules') }}" class="border-top py-2">@csrf
<input type="hidden" name="event_key" value="{{ $event }}"><label>{{ $title }}</label>
<input class="form-control mb-2" name="title" maxlength="150" required value="{{ $configuredRule?->title ?? $title }}">
<div class="form-row"><div class="col"><label>Coins</label><input type="number" class="form-control" required name="coin_reward" min="0" max="1000" value="{{ $configuredRule?->coin_reward ?? 0 }}"></div>
<div class="col"><label>Max per member</label><input type="number" class="form-control" required name="max_per_user" min="1" max="20" value="{{ $configuredRule?->max_per_user ?? 1 }}"></div></div>
<label><input type="checkbox" name="active" value="1" @checked((bool)($configuredRule?->active ?? false))> Enabled</label>
<button class="btn btn-outline-primary btn-sm">Save</button></form>
@endforeach</section>
<form method="POST" action="{{ route('admin.client.scope.rewards.rules') }}">@csrf<input type="hidden" name="event_key" value="got_match">
<div class="form-group"><label>Title</label><input class="form-control" required name="title" value="Got Match confirmation"></div>
<div class="form-group"><label>Coins rewarded</label><input type="number" min="0" max="1000" required class="form-control" name="coin_reward" value="0"></div>
<div class="form-group"><label>Maximum per user</label><input type="number" min="1" max="20" required class="form-control" name="max_per_user" value="1"></div>
<label><input type="checkbox" name="active" value="1"> Enable automatic reward</label><div class="mt-2"><button class="btn btn-primary">Save reward rule</button></div>
</form><hr>
@foreach($rewardRules as $rule)<p><strong>{{ $rule->event_key }}</strong>: {{ $rule->coin_reward }} coins · {{ $rule->active ? 'Active' : 'Disabled' }} · Max {{ $rule->max_per_user }}/member</p>@endforeach
</div></div></div>
<div class="col-lg-7 mb-4"><div class="card"><div class="card-header"><h2 class="h6 mb-0">Latest support ratings</h2></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>Ticket</th><th>Rating</th><th>Comment</th></tr></thead><tbody>
@forelse($ratings as $ticket)<tr><td>{{ $ticket->ticket_id }}</td><td>{{ $ticket->rating }}/5</td><td>{{ $ticket->rating_comment }}</td></tr>@empty<tr><td colspan="3">No ratings yet</td></tr>@endforelse
</tbody></table></div></div></div></div>
@endsection
