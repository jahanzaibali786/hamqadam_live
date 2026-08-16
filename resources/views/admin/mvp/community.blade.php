@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('Community Moderation') }}</h1>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Threads') }}</h5></div>
            <div class="card-body">
                @foreach ($threads as $thread)
                    <div class="border-bottom pb-3 mb-3">
                        <h6>{{ $thread->title }}</h6>
                        <div class="text-muted fs-12 mb-2">{{ $thread->forum?->name }} / {{ $thread->user?->first_name }} {{ $thread->user?->last_name }}</div>
                        <p>{{ Str::limit($thread->body, 140) }}</p>
                        <form action="{{ route('admin.mvp.community.threads.update', $thread->id) }}" method="POST" class="d-flex align-items-center">
                            @csrf
                            <select name="moderation_status" class="form-control form-control-sm mr-2">
                                @foreach (['pending', 'approved', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected($thread->moderation_status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <label class="aiz-checkbox mr-2 mb-0">
                                <input type="checkbox" name="is_locked" value="1" @checked($thread->is_locked)>
                                <span>{{ translate('Locked') }}</span>
                                <span class="aiz-square-check"></span>
                            </label>
                            <button class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </form>
                    </div>
                @endforeach
                <div class="aiz-pagination">{{ $threads->links() }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Posts') }}</h5></div>
            <div class="card-body">
                @foreach ($posts as $post)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="text-muted fs-12 mb-2">{{ $post->thread?->title }} / {{ $post->user?->first_name }} {{ $post->user?->last_name }}</div>
                        <p>{{ Str::limit($post->body, 180) }}</p>
                        <form action="{{ route('admin.mvp.community.posts.update', $post->id) }}" method="POST" class="d-flex">
                            @csrf
                            <select name="moderation_status" class="form-control form-control-sm mr-2">
                                @foreach (['pending', 'approved', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected($post->moderation_status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </form>
                    </div>
                @endforeach
                <div class="aiz-pagination">{{ $posts->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
