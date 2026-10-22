@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference hm-parity-editorial hm-parity-stories">
<header class="hm-reference-heading"><div><span class="hm-ref-kicker">Community journeys</span><h1>Happy Stories</h1><p>Real people. Real connections. Real happiness.</p></div></header>
<nav class="hm-parity-filter-tabs" aria-label="Stories navigation"><span class="current"><i class="las la-th-large"></i> All Stories</span><a href="{{ route('happy_stories') }}"><i class="las la-heart"></i> More Stories</a><a href="{{ route('happy_story.member') }}"><i class="las la-plus"></i> Share Your Story</a></nav>
<div class="hm-parity-editorial-grid"><main>
<div class="hm-ref-articles">@forelse($stories as $story)
@php($photos=array_values(array_filter(array_map('trim',explode(',',(string)$story->photos)))))
<article class="hm-ref-article"><a href="{{ route('story_details',\App\Support\StoryPublicId::encode((int)$story->id)) }}">@if(count($photos))<img src="{{ uploaded_asset($photos[0]) }}" loading="lazy" alt="{{ $story->title }}">@else<div class="hm-ref-story-placeholder"><i class="las la-heart"></i></div>@endif</a><div><small><i class="las la-heart"></i> Community Story · {{ $story->created_at?->format('M Y') }}</small><h2><a href="{{ route('story_details',\App\Support\StoryPublicId::encode((int)$story->id)) }}">{{ $story->title }}</a></h2><p>{{ \Illuminate\Support\Str::limit(strip_tags((string)$story->details),130) }}</p><a class="hm-ref-article-link" href="{{ route('story_details',\App\Support\StoryPublicId::encode((int)$story->id)) }}">Read Full Story →</a></div></article>
@empty<div class="hm-ref-white">No approved stories have been published yet.</div>@endforelse
</div><div class="mt-4">{{ $stories->links() }}</div></main>
<aside><section class="hm-ref-white"><h2><i class="las la-heart"></i> Community Stories</h2><div class="hm-parity-story-metric"><i class="las la-heart"></i><div><strong>{{ number_format($stories->total()) }}</strong><small>Published journeys</small></div></div><p class="small text-muted">Every story displayed here has been approved for publication.</p></section>
<section class="hm-ref-white"><h2>Discover More</h2><a class="hm-parity-popular" href="{{ route('happy_stories') }}"><i class="las la-search"></i><b>Browse all public stories</b><i class="las la-angle-right"></i></a><a class="hm-parity-popular" href="{{ route('happy_story.member') }}"><i class="las la-edit"></i><b>Submit your own story</b><i class="las la-angle-right"></i></a></section>
<section class="hm-ref-help"><i class="las la-quote-left"></i><p>These are not just stories. These are new beginnings.</p></section></aside></div></div>
@endsection