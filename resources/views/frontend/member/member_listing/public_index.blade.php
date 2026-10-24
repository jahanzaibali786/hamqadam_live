@extends('frontend.layouts.app')
@section('meta_title', translate('Active Members Discovery') . ' | Hamqadam')
@section('content')
@include('frontend.member.member_listing.partials.content')
@endsection
@section('modal')
@include('frontend.member.member_listing.partials.modals')
@endsection
@section('script')
@include('frontend.member.member_listing.partials.scripts')
@endsection
