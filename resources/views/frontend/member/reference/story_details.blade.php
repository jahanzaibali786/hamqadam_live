@extends('frontend.layouts.member_panel')
@section('panel_content')
@php($photos=array_values(array_filter(explode(',',(string)$story->photos))))
<div class="hm-blog-26"><header class="hm-blog-heading"><div><h1>{{ $story->title }}</h1><p>Hamqadam Community Story · {{ $story->created_at?->format('M Y') }}</p></div><a href="{{ route('member.reference.stories') }}">← All Stories</a></header><article class="hm-blog-panel">@if(count($photos))<img style="width:100%;max-height:390px;object-fit:cover;border-radius:12px;margin-bottom:17px" src="{{ uploaded_asset($photos[0]) }}" alt="{{ $story->title }}">@endif<div>{!! nl2br(e($story->details)) !!}</div></article></div>
@endsection