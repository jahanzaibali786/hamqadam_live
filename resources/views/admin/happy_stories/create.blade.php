@extends('admin.layouts.app')
@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Add New Happy Story') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('happy-story.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label" for="user_id">{{ translate('Member / Author') }} <span class="text-danger">*</span></label>
                        <select class="form-control aiz-selectpicker" name="user_id" id="user_id" data-live-search="true" required>
                            <option value="">{{ translate('Select Member') }}</option>
                            <option value="{{ Auth::id() }}">{{ translate('Admin Post') }} ({{ Auth::user()->first_name }} {{ Auth::user()->last_name }})</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email ?: $user->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="title">{{ translate('Story Title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" placeholder="{{ translate('Example: A Beautiful Journey from First Intro to Nikah') }}" required>
                        @error('title')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="partner_name">{{ translate('Partner Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="partner_name" id="partner_name" value="{{ old('partner_name') }}" class="form-control" placeholder="{{ translate('Partner Full Name') }}" required>
                        @error('partner_name')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="details">{{ translate('Story Details') }} <span class="text-danger">*</span></label>
                        <textarea name="details" id="details" class="aiz-text-editor form-control" data-buttons='[["font", ["bold", "underline", "italic"]],["para", ["ul", "ol"]],["view", ["undo","redo"]]]' placeholder="{{ translate('Write the inspiring story...') }}" data-min-height="200" required>{{ old('details') }}</textarea>
                        @error('details')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ translate('Photos') }} <span class="text-danger">*</span></label>
                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="photos" value="{{ old('photos') }}" class="selected-files" required>
                        </div>
                        <div class="file-preview box sm"></div>
                        @error('photos')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ translate('Video Provider') }}</label>
                        <select class="form-control aiz-selectpicker" name="video_provider" id="video_provider">
                            <option value="youtube" @selected(old('video_provider') == 'youtube')>{{ translate('Youtube') }}</option>
                            <option value="dailymotion" @selected(old('video_provider') == 'dailymotion')>{{ translate('Dailymotion') }}</option>
                            <option value="vimeo" @selected(old('video_provider') == 'vimeo')>{{ translate('Vimeo') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ translate('Video Link') }}</label>
                        <input type="text" name="video_link" value="{{ old('video_link') }}" class="form-control" placeholder="{{ translate('Video URL (optional)') }}">
                        <small class="text-muted">{{ translate("Use proper link without extra parameter. Don't use short share link/embeded iframe code.") }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label d-block">{{ translate('Approval Status') }}</label>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="approved" value="1" checked>
                            <span class="slider round"></span>
                        </label>
                        <span class="ml-2 text-muted fs-12">{{ translate('Publish immediately on the website') }}</span>
                    </div>

                    <div class="form-group mb-0 text-right">
                        <a href="{{ route('happy-story.index') }}" class="btn btn-light mr-2">{{ translate('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Happy Story') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
