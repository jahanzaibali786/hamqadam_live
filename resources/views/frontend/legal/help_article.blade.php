@extends('frontend.layouts.app')
@section('meta_title', $article['title'] . ' | Hamqadam Help Center')
@section('meta_description', $article['description'])
@section('meta')
<link rel="canonical" href="{{ route('help_center.article', $slug) }}">
@if(!empty($preview))<meta name="robots" content="noindex,nofollow">@endif
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-legal-pages-20261120.css') }}?v=1">
@endsection
@section('content')
<main class="hql-legal-page"><div class="hql-wrap">
 @if(!empty($preview))<div class="hql-preview">ADMIN PREVIEW — Not publicly published</div>@endif
 <div class="hql-article">
   <nav class="hql-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('help_center.index') }}">Help Center</a><span>/</span><span>{{ $article['category'] }}</span></nav>
   <header class="hql-header">
     <span class="hql-kicker">{{ !empty($article['is_faq']) ? 'Frequently Asked Question' : $article['category'] }}</span>
     <h1>{{ $article['title'] }}</h1>
     <p>{{ $article['description'] }}</p>
   </header>
   <div class="hql-section hql-rich">{!! $article['body'] !!}</div>
   <div class="hql-help-cta"><div><h2>Need more help?</h2><p>Reach our team for account or payment assistance.</p></div><a href="{{ route('contact_us') }}">Contact Support</a></div>
 </div>
</div></main>
@endsection
