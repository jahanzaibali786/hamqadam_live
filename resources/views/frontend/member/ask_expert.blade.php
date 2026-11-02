@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-expert-panel" style="max-width:1100px;margin:auto;padding:22px">
  <h1>Ask an Expert</h1><p class="text-muted">Ask a relationship or marriage question and receive a response from the Hamqadam expert team.</p>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <div class="card mb-4"><div class="card-body">
    <h2 class="h5">Submit your question</h2>
    <form method="POST" action="{{ route('member.expert.store') }}">@csrf
      <label for="expert-category">Category</label><select id="expert-category" name="category" class="form-control" required>@foreach(['relationship'=>'Relationship','marriage'=>'Marriage','family'=>'Family','communication'=>'Communication','other'=>'Other'] as $key=>$label)<option value="{{ $key }}" @selected(old('category')===$key)>{{ $label }}</option>@endforeach</select>
      <label class="mt-3" for="expert-question">Question</label><input id="expert-question" name="question" class="form-control" required minlength="10" maxlength="255" value="{{ old('question') }}" placeholder="What would you like to ask?">
      <label class="mt-3" for="expert-details">Additional details (optional)</label><textarea id="expert-details" name="details" class="form-control" rows="5" maxlength="3000">{{ old('details') }}</textarea>
      <label class="d-block mt-3"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous',true))> Show anonymously in public expert answers</label>
      <button type="submit" class="btn btn-primary mt-3" style="background:linear-gradient(135deg,#9f1239,#67051f)!important;color:white">Submit Question</button>
    </form>
  </div></div>
  <h2 class="h5">My Questions</h2>
  @forelse($questions as $item)
  <article class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between flex-wrap"><a href="{{ route('member.expert.conversation',$item->id) }}"><strong>{{ $item->question }}</strong> <i class="las la-comment-dots"></i> Open Conversation</a><span class="badge badge-secondary">{{ ucfirst($item->status) }}</span></div>
    <p class="text-muted mt-2">{{ $item->details }}</p>
    @if($item->status==='answered')<div class="p-3 rounded" style="background:#f8f6f7"><b>Expert Response @if($item->expert_name) — {{ $item->expert_name }} @endif</b><p class="mb-0 mt-2">{{ $item->answer }}</p></div>@elseif($item->status==='rejected')<p class="text-muted">This question was not approved for answering.</p>@else <p class="text-muted">Awaiting expert review.</p>@endif
  </div></article>
  @empty <p>No questions submitted yet.</p>
  @endforelse
  {{ $questions->links() }}
</div>
@endsection