@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference hm-parity-editorial">
<header class="hm-reference-heading"><div><span class="hm-ref-kicker">Knowledge and guidance</span><h1>Blogs &amp; Articles</h1><p>Useful insights, guidance and stories for a better tomorrow.</p></div></header>
<nav class="hm-parity-filter-tabs" aria-label="Article categories">
<a href="{{ route('member.reference.blogs') }}" class="{{ $search===''?'current':'' }}"><i class="las la-th-large"></i> All Articles</a>
<a href="{{ route('member.reference.blogs',['search'=>'relationship']) }}"><i class="las la-heart"></i> Relationship Tips</a>
<a href="{{ route('member.reference.blogs',['search'=>'marriage']) }}"><i class="las la-ring"></i> Marriage Guidance</a>
<a href="{{ route('member.reference.blogs',['search'=>'family']) }}"><i class="las la-users"></i> Family &amp; Culture</a>
<a href="{{ route('member.reference.blogs',['search'=>'personal']) }}"><i class="las la-chart-line"></i> Personal Growth</a>
</nav>
<div class="hm-parity-editorial-grid"><main>
@php($featured=$blogs->first())
@if($featured && $blogs->currentPage()===1 && $search==='')
<a class="hm-parity-featured" href="{{ route('blog.details',$featured->slug) }}"><div><small>★ Featured Article</small><h2>{{ $featured->title }}</h2><p>{{ \Illuminate\Support\Str::limit(strip_tags((string)$featured->short_description),160) }}</p><span>Read Article →</span></div><img src="{{ uploaded_asset($featured->banner) }}" alt="{{ $featured->title }}"></a>
@endif
<section class="hm-ref-white"><div class="hm-ref-section-title"><h2>Latest Articles</h2><span>{{ $blogs->total() }} articles</span></div>
<div class="hm-ref-articles">@forelse($blogs as $blog)
<article class="hm-ref-article"><a href="{{ route('blog.details',$blog->slug) }}"><img loading="lazy" src="{{ uploaded_asset($blog->banner) }}" alt="{{ $blog->title }}"></a><div><small>{{ $blog->category?->category_name ?? 'Marriage Advice' }} · {{ $blog->created_at?->format('M d, Y') }}</small><h2><a href="{{ route('blog.details',$blog->slug) }}">{{ $blog->title }}</a></h2><p>{{ \Illuminate\Support\Str::limit(strip_tags((string)$blog->short_description),125) }}</p><a class="hm-ref-article-link" href="{{ route('blog.details',$blog->slug) }}">Read Article →</a></div></article>
@empty<div class="hm-ref-white">No articles found.</div>@endforelse
</div><div class="mt-4">{{ $blogs->links() }}</div></section></main>
<aside><section class="hm-ref-white"><h2><i class="las la-search"></i> Search Articles</h2><form class="hm-parity-search" action="{{ route('member.reference.blogs') }}" method="GET"><input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Search articles..."><button type="submit" class="hm-ref-cta" aria-label="Search articles"><i class="las la-search"></i></button></form></section>
<section class="hm-ref-white"><h2>Popular Reads</h2>@foreach($blogs->take(5) as $blog)<a class="hm-parity-popular" href="{{ route('blog.details',$blog->slug) }}"><img src="{{ uploaded_asset($blog->banner) }}" alt="" loading="lazy"><div><b>{{ \Illuminate\Support\Str::limit($blog->title,60) }}</b><small>{{ $blog->created_at?->format('d M Y') }}</small></div><i class="las la-angle-right"></i></a>@endforeach</section>
<section class="hm-ref-help"><i class="las la-book-open"></i><h2>Keep Learning</h2><p>Explore stories and practical guidance written for meaningful connections.</p></section></aside></div></div>
@endsection