@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mb-3">
  <h1 class="h3">{{ translate('Support Ticket Management') }}</h1>
  <p class="text-muted">Review member support requests, replies, response ETA and ratings.</p>
</div>
@if(!$ready)
<div class="alert alert-warning" role="alert">Support ticket tables are not installed yet. Apply the reviewed support migration to activate this section. Existing Help Center chats are separate.</div>
@else
<div class="card"><div class="card-body table-responsive">
<table class="table table-striped">
<thead><tr><th>Ticket</th><th>Member ID</th><th>Subject</th><th>Status</th><th>Language</th><th>ETA</th><th>Rating</th><th>Replies</th></tr></thead>
<tbody>
@forelse($tickets as $ticket)
<tr>
 <td>#{{ $ticket->ticket_id }}</td>
 <td>{{ $ticket->sender_user_id }}</td>
 <td>{{ $ticket->subject }}</td>
 <td>{{ $ticket->status_label }}</td>
 <td>{{ strtoupper($ticket->preferred_language ?? 'en') }}</td>
 <td>{{ $ticket->expected_response_at?->format('d M Y H:i') ?? '—' }}</td>
 <td>{{ $ticket->rating ? $ticket->rating.'/5' : '—' }}</td>
 <td>
  <details><summary>View conversation</summary>
   <p>{{ $ticket->description }}</p>
   @forelse($ticket->replies as $reply)<p><small>By user #{{ $reply->replied_user_id }}</small>: {{ $reply->reply }}</p>@empty<p>No replies yet.</p>@endforelse
  </details>
  <form method="POST" action="{{ route('support-ticket.admin_reply') }}" class="mt-2">@csrf
   <input type="hidden" name="support_ticket_id" value="{{ $ticket->id }}">
   <label for="reply-{{ $ticket->id }}" class="small">Agent reply</label>
   <textarea id="reply-{{ $ticket->id }}" name="reply" class="form-control mb-2" maxlength="10000" required></textarea>
   <select name="status" class="form-control mb-2" aria-label="Update ticket status">
    @foreach([0=>'Open',2=>'In progress',3=>'Waiting for member',1=>'Resolved'] as $value=>$label)
     <option value="{{ $value }}" @selected((int)$ticket->status === $value)>{{ $label }}</option>
    @endforeach
   </select>
   <button class="btn btn-primary btn-sm">Save reply & status</button>
  </form>
 </td>
</tr>
@empty<tr><td colspan="8">No support tickets yet.</td></tr>@endforelse
</tbody></table>{{ $tickets->links() }}</div></div>
@endif
@endsection
