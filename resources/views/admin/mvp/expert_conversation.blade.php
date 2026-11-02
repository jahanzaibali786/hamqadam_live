@extends('admin.layouts.app')
@section('content')
<div class="mb-3"><a href="{{ route('admin.mvp.expert_questions') }}">← Advisor Consultation / Expert Queries</a></div>
<div class="card"><div class="card-header"><h1 class="h5 mb-0">Expert Conversation #{{ $question->id }} — {{ $question->question }}</h1><small>{{ $question->user?->first_name }} {{ $question->user?->last_name }} · {{ ucfirst($question->status) }}</small></div>
<div class="card-body">
 <div class="p-3 mb-3 border rounded"><strong>Original question</strong><p>{{ $question->details ?: $question->question }}</p></div>
 @if($question->answer)<div class="p-3 mb-3 border rounded"><b>Previous expert answer</b><p>{{ $question->answer }}</p></div>@endif
 <div style="max-height:440px;overflow:auto" aria-label="Advisor conversation">
 @forelse($messages as $message)<div class="d-flex mb-3 {{ $message->sender_id===$question->user_id?'justify-content-start':'justify-content-end' }}"><div class="p-3 rounded" style="max-width:83%;background:{{ $message->sender_id===$question->user_id?'#f4f4f4':'#f8f0f3' }}"><b>{{ $message->sender_id===$question->user_id?'Member':'Advisor' }}</b><p class="mb-1">{{ $message->body }}</p><small>{{ $message->created_at?->format('d M Y H:i') }}</small></div></div>
 @empty<p class="text-muted">No follow-up messages yet.</p>@endforelse
 </div>{{ $messages->links() }}
 <form method="POST" action="{{ route('admin.mvp.expert_questions.reply',$question->id) }}">@csrf
 <label for="advisor-reply">Write reply</label><textarea id="advisor-reply" name="body" rows="4" maxlength="5000" required class="form-control">{{ old('body') }}</textarea>
 @if($errors->any())<p class="text-danger">{{ $errors->first() }}</p>@endif
 <button class="btn btn-primary mt-3" type="submit">Send Reply</button></form>
</div></div>
@endsection