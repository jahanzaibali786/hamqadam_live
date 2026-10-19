@extends('frontend.layouts.member_panel')
@section('panel_content')
<main class="container py-5"><h1 class="h3">Support Center</h1>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
@if(!$supportReady)
<div class="alert alert-warning" role="status">Support ticket setup is incomplete. An administrator must apply the support tables migration. Existing Help Center chat is available from the member dashboard.</div>
@else
<div class="card mb-4"><div class="card-body"><h2 class="h5">Create support ticket</h2>
<form method="POST" action="{{ route('support-ticket.store') }}">@csrf
<div class="form-group"><label for="subject">Subject</label><input id="subject" class="form-control" name="subject" required maxlength="255" value="{{ old('subject') }}"></div>
<div class="form-group"><label for="description">Describe the issue</label><textarea class="form-control" rows="4" name="description" id="description">{{ old('description') }}</textarea></div>
<div class="form-group"><label for="support-language">{{ translate('Preferred support language') }}</label><select name="preferred_language" id="support-language" class="form-control">
@foreach(['en'=>'English','ur'=>'اردو','ar'=>'العربية','hi'=>'हिन्दी'] as $code=>$label)<option value="{{ $code }}" @selected(old('preferred_language',app()->getLocale())===$code)>{{ $label }}</option>@endforeach
</select><small class="text-muted">Messages are delivered as written; selecting a language does not automatically translate private text.</small></div>
<button class="btn btn-primary">Submit ticket</button></form></div></div>
<h2 class="h4">Your tickets</h2>
@forelse($tickets as $ticket)
<article class="card mb-3"><div class="card-body">
<h3 class="h6">{{ $ticket->subject }}</h3><p class="small text-muted">{{ translate('Conversation with your support team') }} · #{{ $ticket->ticket_id }}</p>
<p>Status: <strong>{{ $ticket->status_label }}</strong> · Preferred language: {{ strtoupper($ticket->preferred_language ?? 'EN') }}
@if($ticket->expected_response_at && (string)$ticket->status !== '1') · Expected first response: {{ $ticket->expected_response_at->format('d M Y H:i') }} @endif</p>
@if($ticket->description)<p>{{ $ticket->description }}</p>@endif
@foreach($ticket->replies as $reply)<blockquote class="border-left pl-3"><p>{{ $reply->reply }}</p><small>{{ $reply->created_at }}</small></blockquote>@endforeach
@if((string)$ticket->status !== '1')
<form method="POST" action="{{ route('support-ticket.user_reply') }}">@csrf
<input type="hidden" name="support_ticket_id" value="{{ $ticket->id }}">
<label for="reply-{{ $ticket->id }}">Reply</label><textarea id="reply-{{ $ticket->id }}" name="reply" class="form-control mb-2" required></textarea>
<button class="btn btn-outline-primary btn-sm">Send reply</button></form>
@elseif(!$ticket->rated_at)
<form method="POST" action="{{ route('support-tickets.rate',$ticket->id) }}">@csrf
<label for="rating-{{ $ticket->id }}">Rate this closed ticket</label>
<select id="rating-{{ $ticket->id }}" class="form-control mb-2" name="rating" required><option value="">Choose</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select>
<textarea name="comment" class="form-control mb-2" placeholder="Optional feedback" maxlength="1000"></textarea>
<button class="btn btn-primary btn-sm">Save rating</button></form>
@else <p class="text-success">You rated this ticket {{ $ticket->rating }}/5.</p>
@endif
</div></article>
@empty<p>No support tickets yet.</p>@endforelse
{{ $tickets->links() }}
@endif
</main>
@endsection
