@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="container-fluid py-4"><a href="{{ route('member.expert.index') }}">← Ask an Expert</a><h1 class="h4 mt-3">{{ $question->question }}</h1><p>{{ $question->details }}</p>
@if($question->answer)<div class="card p-3 mb-3"><b>Expert Answer</b><p>{{ $question->answer }}</p></div>@endif
<div class="card"><div class="card-body"><div style="max-height:420px;overflow:auto">
@forelse($messages as $message)<div class="d-flex mb-3 {{ $message->sender_id===auth()->id()?'justify-content-end':'justify-content-start' }}"><div class="p-3 rounded" style="max-width:85%;background:#f8f6f7"><b>{{ $message->sender_id===auth()->id()?'You':'Advisor' }}</b><p class="mb-1">{{ $message->body }}</p><small>{{ $message->created_at?->format('d M Y H:i') }}</small></div></div>@empty<p>No conversation replies yet.</p>@endforelse
</div>{{ $messages->links() }}
<form method="POST" action="{{ route('member.expert.reply',$question->id) }}">@csrf
<label for="member-advisor-reply">Reply to advisor</label><textarea id="member-advisor-reply" name="body" class="form-control" maxlength="5000" rows="3" required></textarea>
<button type="submit" class="btn btn-primary mt-3">Send Message</button></form></div></div></div>
@endsection