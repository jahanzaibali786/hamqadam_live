@extends('admin.layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{ translate('Chat Real-Time Configuration') }}</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('chat_realtime_settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Enable Real-Time Chat') }}</label>
                        </div>
                        <div class="col-md-9">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input value="1" name="chat_realtime_enabled" type="checkbox" @if (get_setting('chat_realtime_enabled') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                            <small class="text-muted">{{ translate('When disabled, the system falls back to HTTP polling.') }}</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Provider') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" value="Pusher" disabled>
                            <small class="text-muted">{{ translate('Currently supported real-time provider.') }}</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('App ID') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="pusher_app_id" value="{{ get_setting('pusher_app_id') }}" placeholder="{{ translate('Pusher App ID') }}" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('App Key') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="pusher_app_key" value="{{ get_setting('pusher_app_key') }}" placeholder="{{ translate('Pusher App Key') }}" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('App Secret') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="password" class="form-control" name="pusher_app_secret" value="{{ get_setting('pusher_app_secret') }}" placeholder="{{ translate('Pusher App Secret') }}" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Cluster / Region') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="pusher_app_cluster" value="{{ get_setting('pusher_app_cluster', 'mt1') }}" placeholder="{{ translate('e.g. mt1, eu, ap2') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Host') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="pusher_host" value="{{ get_setting('pusher_host', 'ws-ap1.pusher.com') }}" placeholder="{{ translate('e.g. ws-ap1.pusher.com') }}">
                            <small class="text-muted">{{ translate('Leave default unless using a custom Pusher-compatible server.') }}</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Port') }}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="pusher_port" value="{{ get_setting('pusher_port', 443) }}" placeholder="{{ translate('e.g. 443') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{ translate('Scheme') }}</label>
                        </div>
                        <div class="col-md-9">
                            <select class="form-control aiz-selectpicker" name="pusher_scheme">
                                <option value="https" @if (get_setting('pusher_scheme', 'https') == 'https') selected @endif>https</option>
                                <option value="http" @if (get_setting('pusher_scheme') == 'http') selected @endif>http</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
