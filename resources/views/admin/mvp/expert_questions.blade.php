@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('Ask Expert Moderation') }}</h1>
</div>
<div class="card">
    <div class="card-body">
        @foreach ($questions as $question)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="badge badge-inline badge-info">{{ ucfirst($question->category) }}</span>
                        <span class="badge badge-inline badge-{{ $question->status === 'answered' ? 'success' : ($question->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($question->status) }}</span>
                    </div>
                    <small>{{ $question->created_at }}</small>
                </div>
                <h6 class="mt-2">{{ $question->question }}</h6>
                <p class="text-muted">{{ $question->details }}</p>
                <form action="{{ route('admin.mvp.expert_questions.answer', $question->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <input class="form-control" name="expert_name" value="{{ $question->expert_name }}" placeholder="{{ translate('Expert name') }}">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="status">
                                @foreach (['pending', 'answered', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected($question->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mt-2">
                            <textarea class="form-control" name="answer" rows="4" placeholder="{{ translate('Answer') }}">{{ $question->answer }}</textarea>
                        </div>
                        <div class="col-md-12 mt-2 text-right">
                            <button class="btn btn-primary">{{ translate('Save Answer') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
        <div class="aiz-pagination">{{ $questions->links() }}</div>
    </div>
</div>
@endsection
