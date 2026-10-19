@extends('frontend.layouts.member_panel')
@section('panel_content')
<main class="container py-5" style="max-width:900px"><h1 class="h3">Notification Preferences &amp; History</h1>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card mb-4"><div class="card-body"><h2 class="h5">Your delivery settings</h2>
<form method="post" action="{{ route('client.scope.notifications.save') }}">@csrf
<div class="form-group"><label><input type="checkbox" name="push_enabled" value="1" @checked($preferences->push_enabled)> Push notifications</label></div>
<div class="form-group"><label><input type="checkbox" name="email_enabled" value="1" @checked($preferences->email_enabled)> Email notifications</label></div>
<div class="form-group"><label><input type="checkbox" name="engagement_enabled" value="1" @checked($preferences->engagement_enabled)> Engagement reminders</label></div>
<div class="form-row">
<div class="col-md-4 form-group"><label for="timezone">Time zone</label><input class="form-control" id="timezone" name="timezone" value="{{ old('timezone',$preferences->timezone ?: 'Asia/Karachi') }}" required></div>
<div class="col-md-4 form-group"><label for="quiet-start">Quiet hours start</label><input class="form-control" id="quiet-start" type="time" name="quiet_hours_start" value="{{ old('quiet_hours_start',substr((string)$preferences->quiet_hours_start,0,5)) }}"></div>
<div class="col-md-4 form-group"><label for="quiet-end">Quiet hours end</label><input class="form-control" id="quiet-end" type="time" name="quiet_hours_end" value="{{ old('quiet_hours_end',substr((string)$preferences->quiet_hours_end,0,5)) }}"></div>
<div class="col-md-4 form-group"><label for="reminder-interval">Reminder interval (minutes)</label><input class="form-control" id="reminder-interval" type="number" min="60" max="10080" required name="reminder_interval_minutes" value="{{ old('reminder_interval_minutes',$preferences->reminder_interval_minutes ?? 1440) }}"></div>
<div class="col-md-4 form-group"><label for="reminder-max">Max reminder attempts</label><input class="form-control" id="reminder-max" type="number" min="0" max="10" required name="reminder_max_attempts" value="{{ old('reminder_max_attempts',$preferences->reminder_max_attempts ?? 3) }}"></div>
<div class="col-md-4 form-group"><label for="digest">Digest preference</label><select class="form-control" name="digest_frequency" id="digest">
@foreach(['instant','daily','weekly','off'] as $freq)<option value="{{ $freq }}" @selected(old('digest_frequency',$preferences->digest_frequency ?? 'instant')===$freq)>{{ ucfirst($freq) }}</option>@endforeach
</select></div>
</div>
<p class="small text-muted">Quiet hours are enforced by the unread-push-reminder command. Digest preference is stored but digest email sending is not yet implemented.</p>
<button type="submit" class="btn btn-primary">Save preferences</button></form></div></div>
<h2 class="h5">Delivery history</h2><div class="card"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Channel</th><th>Status</th><th>Delivered</th><th>Read</th><th>Reminders</th></tr></thead><tbody>
@forelse($history as $log)<tr><td>{{ strtoupper($log->channel) }}</td><td>{{ $log->status }}</td><td>{{ $log->delivered_at ?? '—' }}</td><td>{{ $log->read_at ?? '—' }}</td><td>{{ $log->reminder_count }}</td></tr>
@empty<tr><td colspan="5">No delivery events found.</td></tr>@endforelse
</tbody></table>{{ $history->links() }}</div></div></main>
@endsection
