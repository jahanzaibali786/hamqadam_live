@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mb-3"><h1 class="h3">{{ translate('Notification Delivery Audit') }}</h1></div>
<p class="text-muted">Delivery states are reported only when recorded. Sent does not mean delivered or read.</p>
<form method="get" class="card card-body mb-3"><div class="row">
<div class="col-md-3 mb-2"><label for="audit-user">User ID</label><input id="audit-user" class="form-control" type="number" min="1" name="user_id" value="{{ request('user_id') }}"></div>
<div class="col-md-3 mb-2"><label for="audit-channel">Channel</label><select id="audit-channel" class="form-control" name="channel"><option value="">All</option>@foreach(['push','email','in_app'] as $channel)<option value="{{ $channel }}" @selected(request('channel')===$channel)>{{ $channel }}</option>@endforeach</select></div>
<div class="col-md-3 mb-2"><label for="audit-state">Status</label><select id="audit-state" class="form-control" name="status"><option value="">All</option>@foreach(['queued','sent','delivered','read','failed'] as $state)<option value="{{ $state }}" @selected(request('status')===$state)>{{ $state }}</option>@endforeach</select></div>
<div class="col-md-3 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filter</button></div>
</div></form>
<div class="card mb-3"><div class="card-body"><h2 class="h6">Recorded delivery states</h2><div class="d-flex flex-wrap">@foreach($summary as $item)<span class="badge badge-light border p-2 m-1">{{ $item->channel }} / {{ $item->status }}: {{ $item->total }}</span>@endforeach</div></div></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>User</th><th>Channel</th><th>Event</th><th>Status</th><th>Sent</th><th>Delivered</th><th>Read</th><th>Reminders</th><th>Error</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->user_id }}</td><td>{{ $log->channel }}</td><td>{{ $log->event_key }}</td><td>{{ $log->status }}</td><td>{{ $log->sent_at }}</td><td>{{ $log->delivered_at }}</td><td>{{ $log->read_at }}</td><td>{{ $log->reminder_count }}</td><td>{{ \Illuminate\Support\Str::limit($log->failure_reason ?? $log->error_message, 100) }}</td></tr>
@empty<tr><td colspan="9">No notification history recorded</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}
@endsection
