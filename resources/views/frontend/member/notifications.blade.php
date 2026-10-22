@extends('frontend.layouts.member_panel')
@section('panel_content')
@include('frontend.member.partials.design_header', ['title' => "Notifications", 'subtitle' => "Stay informed about your matching journey.", 'icon' => "la-bell"])

    <div class="hm-ref-page hm-notifications-reference" data-hm-screen="notifications"><div class="hm-ref-stats">
      <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-bell"></i></span><div><small>Total Notifications</small><strong>{{ $notifications->total() }}</strong></div></div>
      <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-envelope"></i></span><div><small>Unread This Page</small><strong>{{ $notifications->getCollection()->whereNull('read_at')->count() }}</strong></div></div>
      <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-check-circle"></i></span><div><small>Read This Page</small><strong>{{ $notifications->getCollection()->whereNotNull('read_at')->count() }}</strong></div></div>
      <a class="hm-ref-stat text-decoration-none" href="{{ route('client.scope.notifications') }}"><span class="hm-ref-stat-icon"><i class="las la-sliders-h"></i></span><div><small>Preferences</small><strong style="font-size:16px">Manage</strong></div></a>
      </div><div class="hm-parity-notifications-layout"><main><div class="card hm-ref-panel">
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
                            $check = true;
                            $notify_data = is_array($notification->data) ? (object)$notification->data : (is_string($notification->data) ? json_decode($notification->data) : (object)$notification->data);
                            $notify_by = $notify_data->notify_by ?? null;
                            $user = $notify_by ? \App\Models\User::where('id', $notify_by)->first() : null;
                        @endphp
                        @if($notify_data->type == 'express_interest')
                            @php
                                $interest_data = App\Models\ExpressInterest::where('id', $notify_data->info_id)->first();
                                if(empty($interest_data))
                                {
                                    $check = false;
                                }
                            @endphp
                        @endif
                        @if($check)
                            <li class="list-group-item d-flex justify-content-between align-items-start hov-bg-soft-primary">
                                <a href="{{ route('notification_view', $notification->id) }}" class="media text-inherit">
                                    @php
                                        if($user && $user->user_type == 'member'){
                                            $avatar_image = $user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                        } else {
                                            $avatar_image = 'assets/img/avatar-place.png';
                                        }
                                        $profile_picture_show = $user ? show_profile_picture($user) : false;
                                    @endphp
                                    <span class="avatar avatar-sm mr-3">
                                        <img 
                                            @if ($user && ($profile_picture_show || $user->user_type == 'admin'))
                                            src="{{ uploaded_asset($user->photo) }}"
                                            @else
                                            src="{{ static_asset($avatar_image) }}"
                                            @endif
                                        >
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1 font-weight-bold">{{ $notify_data->title ?? translate('Hamqadam Notification') }}</p>
                                        <p class="mb-1">{{ $notify_data->message ?? '' }}</p>
                                        <small class="text-muted">{{ Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</small>
                                    </div>
                                </a>
                                @if($notification->read_at == null)
                                    <button class="btn p-0" data-toggle="tooltip" data-title="{{ translate('New') }}">
                                        <span class="badge badge-md  badge-dot badge-circle badge-primary"></span>
                                    </button>
                                @endif
                            </li>
                        @endif
                    @endforeach
                @else
                    <li class="list-group-item">
                        <div class="text-center">
                            <i class="las la-frown la-4x mb-4 opacity-40"></i>
                            <h4>{{ translate('Nothing Found') }}</h4>
                        </div>
                    </li>
                @endif
            </ul>
            <div class="aiz-pagination aiz-pagination-center">
                {{ $notifications->links() }}
            </div>
        </div>
    </div></main>
    <aside>
      <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2><i class="las la-sliders-h"></i> Notification Filters</h2></div>
      <div class="hm-ref-panel-body"><p class="small text-muted">View important notifications and open the connected member features.</p>
       <div class="hm-ref-side-links">
         <a href="{{ route('frontend.notifications') }}"><i class="las la-bell"></i> All Notifications</a>
         <a href="{{ route('my_matched_profiles') }}"><i class="las la-heart"></i> Matches</a>
         <a href="{{ route('interest_requests') }}"><i class="las la-envelope"></i> Interests</a>
         <a href="{{ route('all.messages') }}"><i class="las la-comments"></i> Messages</a>
       </div></div></section>
      <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2><i class="las la-cog"></i> Notification Settings</h2></div>
      <div class="hm-ref-panel-body"><p class="small text-muted">Control your email, push and quiet-hour preferences using the existing settings page.</p>
       <a class="btn btn-primary btn-block" href="{{ route('client.scope.notifications') }}">Open Preferences →</a></div></section>
      <section class="hm-ref-help"><i class="las la-gem"></i><b>Keep Every Connection</b><p>Discover the available membership features and notification benefits.</p>
      <a class="btn btn-primary btn-block mt-2" href="{{ route('member.reference.packages') }}">View Plans →</a></section>
    </aside>
    </div></div>
@endsection
