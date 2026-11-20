@extends('frontend.layouts.app')
@section('meta_title', $data['seo']['title'])
@section('meta_description', $data['seo']['description'])
@section('meta')
@if(!empty($preview))<meta name="robots" content="noindex,nofollow">@endif
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-editorial.css') }}?v=20261023">
@endsection
@section('content')
<div class="hqed hqed-{{ $pageKey }}">
 @include('frontend.editorial.sections.'.$pageKey)
</div>
@endsection