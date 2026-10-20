@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mb-3"><h1 class="h3">AI Matchmaking API Logs</h1><p>Recorded response diagnostics. Restricted to administrators.</p></div>
<form method="GET" class="card card-body mb-3"><div class="row">
<div class="col-md-3"><label>User ID</label><input type="number" min="1" name="user_id" value="{{ request('user_id') }}" class="form-control"></div>
<div class="col-md-3"><label>Context</label><select name="context" class="form-control"><option value="">All</option>@foreach(['matches','preview'] as $v)<option value="{{ $v }}" @selected(request('context')===$v)>{{ ucfirst($v) }}</option>@endforeach</select></div>
<div class="col-md-3"><label>HTTP status</label><input type="number" min="100" max="599" name="status" value="{{ request('status') }}" class="form-control"></div>
<div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary btn-block">Filter</button></div></div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>User ID</th><th>Candidate</th><th>Context</th><th>HTTP</th><th>Recorded</th><th>Detail</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->id }}</td><td>{{ $log->user_id }}</td><td>{{ $log->candidate_id ?? '—' }}</td><td>{{ $log->context }}</td><td>{{ $log->http_status }}</td><td>{{ $log->created_at }}</td><td><a class="btn btn-sm btn-soft-primary" href="{{ route('admin.matchmaking_logs.show',$log->id) }}" aria-label="Show log {{ $log->id }}"><i class="las la-eye"></i> Show</a></td></tr>
@empty<tr><td colspan="7">No matchmaking logs recorded.</td></tr>@endforelse
</tbody></table>{{ $logs->links() }}</div></div>
@endsection
