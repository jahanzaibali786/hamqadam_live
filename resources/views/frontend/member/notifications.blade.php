@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-notify-v2">
<header class="hm-notify-head"><div class="hm-notify-heading"><i class="las la-bell"></i><div><h1>Notifications</h1><p>Stay informed about your matching journey.</p></div></div><a href="{{ route('member.listing') }}" class="hm-pink-action">Explore Matches <i class="las la-arrow-right"></i></a></header>
<div class="hm-notify-stats"><div><i class="las la-bell"></i><span>Total Notifications<strong>{{ $notifications->total() }}</strong></span></div><div><i class="las la-envelope"></i><span>Unread This Page<strong>{{ $notifications->getCollection()->whereNull('read_at')->count() }}</strong></span></div><div><i class="las la-check-circle"></i><span>Read This Page<strong>{{ $notifications->getCollection()->whereNotNull('read_at')->count() }}</strong></span></div><a href="{{ route('client.scope.notifications') }}"><i class="las la-sliders-h"></i><span>Preferences<strong>Manage</strong></span></a></div>
<div class="hm-notify-layout"><main class="hm-notify-card"><div class="hm-notify-card-head"><h2>Notifications</h2><a href="{{ route('notification.mark_all_as_read') }}">Mark all as read <i class="las la-check-double"></i></a></div>
<div class="hm-notify-list">
@forelse($notifications as $notification)
@php
  $data = is_array($notification->data) ? (object) $notification->data : (object) json_decode((string)$notification->data, true);
  $sender = !empty($data->notify_by) ? \App\Models\User::find($data->notify_by) : null;
  $fallback = static_asset(($sender?->member?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png');
  $photo = $sender?->photo && show_profile_picture($sender) ? uploaded_asset($sender->photo) : $fallback;
@endphp
<a href="{{ route('notification_view',$notification->id) }}" class="hm-notify-item {{ $notification->read_at ? '' : 'is-unread' }}"><img src="{{ $photo }}" alt="" onerror="this.onerror=null;this.src='{{ $fallback }}'"><div><strong>{{ $data->title??'Hamqadam Notification' }}</strong><p>{{ $data->message??'You have a new notification.' }}</p><small>{{ $notification->created_at?->diffForHumans() }}</small></div>@if(!$notification->read_at)<span class="hm-notify-new">New</span>@endif</a>
@empty<div class="hm-notify-empty"><i class="las la-bell-slash"></i><h3>No notifications yet</h3><p>Your important Hamqadam updates will appear here.</p></div>@endforelse
</div><div class="hm-notify-pagination">{{ $notifications->links() }}</div></main>
<aside><section class="hm-notify-card"><h2><i class="las la-filter"></i> Notification Filters</h2><p>Open notifications and related member features.</p><nav class="hm-notify-links"><a href="{{ route('frontend.notifications') }}"><i class="las la-bell"></i> All Notifications <i class="las la-angle-right"></i></a><a href="{{ route('my_matched_profiles') }}"><i class="las la-heart"></i> Matches <i class="las la-angle-right"></i></a><a href="{{ route('my_interests.index') }}"><i class="las la-envelope"></i> Interests <i class="las la-angle-right"></i></a><a href="{{ route('all.messages') }}"><i class="las la-comment-dots"></i> Messages <i class="las la-angle-right"></i></a></nav></section>
<section class="hm-notify-card"><h2><i class="las la-cog"></i> Notification Settings</h2><p>Choose email, push and quiet-hour preferences.</p><a href="{{ route('client.scope.notifications') }}" class="hm-pink-action">Manage Notifications <i class="las la-arrow-right"></i></a></section>
<section class="hm-notify-promo"><i class="las la-heart"></i><h2>Never miss a connection</h2><p>Keep your matching journey moving with the right notification preferences.</p></section></aside></div>
</div>
@endsection