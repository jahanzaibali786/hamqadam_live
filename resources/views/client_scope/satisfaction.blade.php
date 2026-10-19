@extends('frontend.layouts.member_panel')
@section('panel_content')
<main class="container py-5" style="max-width:700px"><h1 class="h3">User satisfaction survey</h1>
  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
  <p>How likely are you to recommend Hamqadam to someone you know?</p>
  <form action="{{ route('client.scope.satisfaction.submit') }}" method="post">@csrf
  <div class="form-group"><label for="score">Score (0–10)</label><select class="form-control" name="score" id="score" required>
    <option value="">Choose a score</option>
    @for($i=0;$i<=10;$i++)<option value="{{ $i }}" @selected(old('score') !== null && (int) old('score') === $i)>{{ $i }}</option>@endfor
  </select></div>
  <div class="form-group"><label for="comment">Additional feedback (optional)</label><textarea id="comment" name="comment" class="form-control" rows="4" maxlength="1500">{{ old('comment') }}</textarea></div>
  <button class="btn btn-primary" type="submit">Submit feedback</button>
  </form><p class="mt-3 small text-muted">Limited to one response every 30 days.</p>
</main>
@endsection
