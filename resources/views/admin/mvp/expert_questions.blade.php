@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('Ask Expert Moderation') }}</h1>
</div>
<form action="{{ route('admin.mvp.expert_questions') }}" method="GET" class="mb-3 d-flex align-items-center" style="gap:10px">
 <label for="expert-status" class="mb-0">Status</label><select id="expert-status" name="status" class="form-control" style="max-width:230px">
 <option value="">All Queries</option>@foreach(['pending','answered','rejected'] as $state)<option value="{{ $state }}" @selected(request('status')===$state)>{{ ucfirst($state) }}</option>@endforeach
 </select><button class="btn btn-primary">Filter</button>
 </form>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover">
<thead><tr><th>ID</th><th>Member</th><th>Question</th><th>Category</th><th>Status</th><th>Submitted</th><th>Conversation</th></tr></thead><tbody>
@forelse($questions as $question)<tr><td>#{{ $question->id }}</td><td>{{ $question->user?->first_name }} {{ $question->user?->last_name }}</td><td>{{ \Illuminate\Support\Str::limit($question->question,80) }}</td><td>{{ ucfirst($question->category) }}</td><td><span class="badge badge-info">{{ ucfirst($question->status) }}</span></td><td>{{ $question->created_at?->format('d M Y H:i') }}</td><td><a class="btn btn-sm btn-soft-primary" href="{{ route('admin.mvp.expert_questions.conversation',$question->id) }}" aria-label="Open conversation for question {{ $question->id }}" title="Reply in conversation"><i class="las la-comment-dots"></i> Reply</a></td></tr>@empty<tr><td colspan="7" class="text-center">No expert queries found.</td></tr>@endforelse
</tbody></table></div>{{ $questions->links() }}</div></div>
@endsection
