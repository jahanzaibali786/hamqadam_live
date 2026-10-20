@extends('admin.layouts.app')
@section('content')
    <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header row gutters-5">
                            <div class="col text-center text-md-left">
                                <h5 class="mb-md-0 h6">{{ translate('Notifications') }}</h5>
                            </div>
                            <div class="col-md-3 text-right">
                                <div class="btn-group mb-2">
                                    <div class="btn-group">
                                        <button type="button" class="btn py-0" data-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="las la-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="{{ route('notification.mark_all_as_read') }}" class="dropdown-item">{{ translate('Mark all as read') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-raw">
                                @if(!$notifications->isEmpty())
                                    @foreach($notifications as $notification)
                                        @php
                                            $notify_data = is_array($notification->data) ? (object)$notification->data : (is_string($notification->data) ? json_decode($notification->data) : (object)$notification->data);
                                            $notify_by = $notify_data->notify_by ?? null;
                                            $user_data = $notify_by ? \App\Models\User::where('id', $notify_by)->first() : null;
                                        @endphp
                                        <li class="list-group-item d-flex justify-content-between align-items-start hov-bg-soft-primary">
                                            <a href="{{ route('notification_view', $notification->id) }}" class="media text-inherit">
                                                <span class="avatar avatar-sm mr-3"><img alt="" src="{{ $user_data && $user_data->photo ? uploaded_asset($user_data->photo) : static_asset('assets/img/avatar-place.png') }}"></span>
                                                <div class="media-body">
                                                    <p class="mb-1 font-weight-bold">{{ $notify_data->title ?? ($user_data ? trim($user_data->first_name.' '.$user_data->last_name) : 'System notification') }}</p>
                                                    <small class="text-muted">{{ $notify_data->message ?? 'Open notification for details.' }}</small>
                                                    <br><small class="text-muted">{{ $notification->created_at?->diffForHumans() }}</small>
                                                </div>
                                            </a>
                                            @if($notification->read_at === null)<span class="badge badge-md badge-dot badge-circle badge-primary" aria-label="Unread"></span>@endif
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            <div class="aiz-pagination">
                                {{ $notifications->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
