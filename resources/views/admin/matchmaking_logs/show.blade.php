@extends('admin.layouts.app')
@section('content')
<div class="mb-3"><a href="{{ route('admin.matchmaking_logs.index') }}">← Back to matchmaking logs</a></div>
<h1 class="h3">Matchmaking Log #{{ $log->id }}</h1>
<div class="card mb-3"><div class="card-body"><dl class="row"><dt class="col-sm-3">Member ID</dt><dd class="col-sm-9">{{ $log->user_id }}</dd><dt class="col-sm-3">Candidate ID</dt><dd class="col-sm-9">{{ $log->candidate_id ?? 'N/A' }}</dd><dt class="col-sm-3">Context</dt><dd class="col-sm-9">{{ $log->context }}</dd><dt class="col-sm-3">HTTP status</dt><dd class="col-sm-9">{{ $log->http_status }}</dd><dt class="col-sm-3">Recorded</dt><dd class="col-sm-9">{{ $log->created_at }}</dd></dl></div></div>
<div class="card"><div class="card-header">Full API response (formatted)</div><div class="card-body"><pre class="p-3 rounded" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:75vh;overflow:auto;background:#f4f6f9;color:#253047" aria-label="Formatted matchmaking API JSON">{{ $body }}</pre></div></div>
@endsection
