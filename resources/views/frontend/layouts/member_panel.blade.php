@extends('frontend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-sidebar-scroll.css') }}">
<section class="hq-member-panel-page py-5">
    <div class="container">
        @include('frontend.member.partials.accessibility_tools')
        <div class="d-flex align-items-start">
            @include('frontend.member.sidebar')
            <div class="aiz-user-panel overflow-hidden">
                @yield('panel_content')
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
@parent
<script src="{{ static_asset('assets/js/hamqadam-member-sidebar-scroll.js') }}" defer></script>
@endsection
