@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('MVP Content Manager') }}</h1>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Forum') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.mvp.content.forums.store') }}" method="POST">
                    @csrf
                    <input class="form-control mb-2" name="name" placeholder="{{ translate('Forum name') }}" required>
                    <textarea class="form-control mb-2" name="description" rows="3" placeholder="{{ translate('Description') }}"></textarea>
                    <label class="aiz-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>{{ translate('Active') }}</span>
                        <span class="aiz-square-check"></span>
                    </label>
                    <button class="btn btn-primary btn-sm">{{ translate('Create') }}</button>
                </form>
                <hr>
                @foreach ($forums as $forum)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $forum->name }}</span>
                        <span class="badge badge-inline badge-{{ $forum->is_active ? 'success' : 'secondary' }}">{{ $forum->is_active ? translate('Active') : translate('Inactive') }}</span>
                    </div>
                @endforeach
                {{ $forums->links() }}
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Webinar') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.mvp.content.webinars.store') }}" method="POST">
                    @csrf
                    <input class="form-control mb-2" name="title" placeholder="{{ translate('Title') }}" required>
                    <textarea class="form-control mb-2" name="description" rows="2" placeholder="{{ translate('Description') }}"></textarea>
                    <input class="form-control mb-2" type="datetime-local" name="starts_at" required>
                    <input class="form-control mb-2" type="number" name="duration_minutes" value="60" min="10" max="240">
                    <input class="form-control mb-2" name="host_name" placeholder="{{ translate('Host name') }}">
                    <input class="form-control mb-2" name="meeting_url" placeholder="{{ translate('Meeting URL') }}">
                    <select class="form-control mb-2" name="status">
                        @foreach (['scheduled', 'live', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">{{ translate('Create') }}</button>
                </form>
                <hr>
                @foreach ($webinars as $webinar)
                    <div class="border-bottom py-2">{{ $webinar->title }} <span class="text-muted">({{ $webinar->status }})</span></div>
                @endforeach
                {{ $webinars->links() }}
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Marriage Tip') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.mvp.content.marriage_tips.store') }}" method="POST">
                    @csrf
                    <input class="form-control mb-2" name="title" placeholder="{{ translate('Title') }}" required>
                    <input class="form-control mb-2" name="category" placeholder="{{ translate('Category') }}">
                    <textarea class="form-control mb-2" name="body" rows="4" placeholder="{{ translate('Tip body') }}" required></textarea>
                    <input class="form-control mb-2" type="datetime-local" name="publish_at">
                    <label class="aiz-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>{{ translate('Active') }}</span>
                        <span class="aiz-square-check"></span>
                    </label>
                    <button class="btn btn-primary btn-sm">{{ translate('Create') }}</button>
                </form>
                <hr>
                @foreach ($tips as $tip)
                    <div class="border-bottom py-2">{{ $tip->title }}</div>
                @endforeach
                {{ $tips->links() }}
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Regional Update') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.mvp.content.regional_updates.store') }}" method="POST">
                    @csrf
                    <input class="form-control mb-2" name="region" placeholder="{{ translate('Region') }}">
                    <input class="form-control mb-2" name="title" placeholder="{{ translate('Title') }}" required>
                    <textarea class="form-control mb-2" name="body" rows="4" placeholder="{{ translate('Update body') }}" required></textarea>
                    <input class="form-control mb-2" type="datetime-local" name="publish_at">
                    <label class="aiz-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>{{ translate('Active') }}</span>
                        <span class="aiz-square-check"></span>
                    </label>
                    <button class="btn btn-primary btn-sm">{{ translate('Create') }}</button>
                </form>
                <hr>
                @foreach ($updates as $update)
                    <div class="border-bottom py-2">{{ $update->title }} <span class="text-muted">{{ $update->region }}</span></div>
                @endforeach
                {{ $updates->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
