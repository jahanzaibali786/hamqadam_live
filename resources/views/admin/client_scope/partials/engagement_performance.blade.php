<section class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Notification engagement reporting (30 days)</h2></div><div class="card-body table-responsive">
<p class="small text-muted">Provider-delivered is only counted if a delivered receipt exists. Read and click totals are based on stored timestamps, not inferred activity.</p>
<table class="table table-sm"><thead><tr><th>Channel / event</th><th>Attempts</th><th>Sent</th><th>Delivered</th><th>Read</th><th>Clicked</th><th>Read rate</th><th>Failures</th><th>Reminders</th></tr></thead><tbody>
@forelse(($report['notification_engagement'] ?? []) as $n)<tr><td>{{ $n['channel'] }} / {{ $n['event'] }}</td><td>{{ $n['attempts'] }}</td><td>{{ $n['sent'] }}</td><td>{{ $n['delivered'] }}</td><td>{{ $n['reads'] }}</td><td>{{ $n['clicks'] }}</td><td>{{ $n['read_rate'] === null ? '—' : $n['read_rate'].'%' }}</td><td>{{ $n['failures'] }}</td><td>{{ $n['reminders'] }}</td></tr>
@empty<tr><td colspan="9">No recorded engagement delivery history.</td></tr>@endforelse
</tbody></table></div></section>
<section class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">HERA / AI recorded recommendation coverage</h2></div><div class="card-body">
@php($hera=$report['hera_performance'] ?? [])
@if($hera)
<div class="row"><div class="col-md-3">Recorded recommendations: <strong>{{ number_format($hera['recommendations']) }}</strong></div>
<div class="col-md-3">AI assisted: <strong>{{ number_format($hera['ai_assisted']) }}</strong></div>
<div class="col-md-3">Mean stored AI match score: <strong>{{ $hera['mean_ai_match_score'] ?? '—' }}</strong></div>
<div class="col-md-3">Staff verified pairs: <strong>{{ number_format($hera['verified_pairs']) }}</strong></div></div>
<p class="small text-muted mt-3">{{ $hera['limitation'] }}</p>
@else<p>No profile match table found.</p>@endif
</div></section>
