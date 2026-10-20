@extends('frontend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-member-sidebar-scroll.css') }}?v=4">
<section class="hq-member-panel-page hq-member-sticky-layout py-5">
    <div class="container">
        <div class="hq-member-panel-viewport">
            @include('frontend.member.partials.accessibility_tools')
            <div class="d-flex align-items-start hq-member-panel-columns">
                @include('frontend.member.sidebar')
                <div class="aiz-user-panel hq-member-main-content">
                    @yield('panel_content')
                </div>
            </div>
        </div>
    </div>
</section>
<script src="{{ static_asset('assets/js/hamqadam-member-sidebar-scroll.js') }}?v=4" defer></script>
@endsection
