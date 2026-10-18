@extends('frontend.layouts.app')
@section('content')
<section class="hq-member-panel-page py-5">
    <div class="container">
        <div class="mb-3 d-flex flex-wrap align-items-center justify-content-end" role="navigation" aria-label="Member quick actions">
            <a href="{{ route('member.referrals.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="las la-user-plus" aria-hidden="true"></i> {{ translate('Refer Friends & Rewards') }}
            </a>
        </div>
        <div class="d-flex align-items-start">
            @include('frontend.member.sidebar')
            <div class="aiz-user-panel overflow-hidden">
                @yield('panel_content')
            </div>
        </div>
    </div>
</section>
@endsection
