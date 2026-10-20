@extends('frontend.layouts.app')
@section('meta_title', $happy_story->title.' | Hamqadam Stories')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string)$happy_story->details),155))
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-stories-v2.css') }}?v=2">
@php
  $photos = array_values(array_filter(array_map('trim',explode(',',(string)$happy_story->photos))));
  $owner = $happy_story->user;
  $byline = trim(($owner?->first_name ?? '').' '.($owner?->last_name ?? ''));
  $description = trim(preg_replace('/\\s+/', ' ', strip_tags((string)$happy_story->details)));
  $videoUrl = null;
  $link = (string)$happy_story->video_link;
  if ($happy_story->video_provider === 'youtube' && preg_match('~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/))([A-Za-z0-9_-]{11})~',$link,$m)) $videoUrl='https://www.youtube-nocookie.com/embed/'.$m[1];
  elseif ($happy_story->video_provider === 'vimeo' && preg_match('~vimeo\\.com/(?:video/)?(\\d+)~',$link,$m)) $videoUrl='https://player.vimeo.com/video/'.$m[1];
  elseif ($happy_story->video_provider === 'dailymotion' && preg_match('~dailymotion\\.com/video/([A-Za-z0-9]+)~',$link,$m)) $videoUrl='https://www.dailymotion.com/embed/video/'.$m[1];
@endphp
<section class="hq-story-detail-page">
  <div class="container">
    <nav class="hq-story-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">{{ translate('Home') }}</a><i class="las la-angle-right"></i><a href="{{ route('happy_stories') }}">{{ translate('Happy Stories') }}</a><i class="las la-angle-right"></i><span>{{ \Illuminate\Support\Str::limit($happy_story->title,55) }}</span></nav>
    <header class="hq-story-detail-header text-center">
      <span class="hq-story-kicker"><i class="las la-heart"></i> {{ translate('A Hamqadam Community Story') }}</span>
      <h1>{{ $happy_story->title }}</h1>
      <div class="hq-story-detail-meta">
        @if($byline !== '')<span><i class="las la-user-circle"></i> {{ $byline }}</span>@endif
        @if($happy_story->created_at)<span><i class="las la-calendar"></i> {{ $happy_story->created_at->format('d F Y') }}</span>@endif
      </div>
    </header>
    <div class="hq-story-detail-shell">
      <div class="hq-story-detail-main">
        @if(count($photos))
          <div class="hq-story-detail-gallery">
            <div class="hq-story-detail-cover">
              <img src="{{ uploaded_asset($photos[0]) }}" alt="{{ $happy_story->title }}" fetchpriority="high" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}'">
            </div>
            @if(count($photos)>1)
              <div class="hq-story-detail-thumbs">
                @foreach(array_slice($photos,1) as $photo)
                <a href="{{ uploaded_asset($photo) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ translate('View story photo') }} {{ $loop->iteration+1 }}"><img src="{{ uploaded_asset($photo) }}" loading="lazy" alt="{{ $happy_story->title }} · {{ $loop->iteration+1 }}"></a>
                @endforeach
              </div>
            @endif
          </div>
        @else
          <div class="hq-story-detail-cover hq-story-detail-placeholder"><i class="las la-heart"></i></div>
        @endif
        <article class="hq-story-article">
          <div class="hq-story-article-eyebrow">{{ translate('Our Journey') }}</div>
          <div class="hq-story-body">{{ $description }}</div>
        </article>
        @if($videoUrl)
        <div class="hq-story-video"><h2>{{ translate('Our Story on Video') }}</h2><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="{{ $videoUrl }}" loading="lazy" title="{{ translate('Happy story video') }}" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div>
        @endif
      </div>
      <aside class="hq-story-detail-aside">
        <div class="hq-story-detail-aside-card">
          <span class="hq-story-kicker"><i class="las la-heart"></i> Hamqadam</span>
          <h2>{{ translate('Every beautiful journey begins with a connection.') }}</h2>
          <p>{{ translate('Explore more community journeys or begin your own with care and confidence.') }}</p>
          <a href="{{ route('happy_stories') }}" class="btn btn-outline-primary btn-block">{{ translate('Explore More Stories') }}</a>
          <a href="{{ auth()->check() ? route('happy_story.member') : route('login') }}" class="btn btn-primary btn-block">{{ translate('Share Your Story') }}</a>
        </div>
      </aside>
    </div>
    <div class="hq-story-bottom-link"><a href="{{ route('happy_stories') }}"><i class="las la-arrow-left"></i> {{ translate('Back to all happy stories') }}</a></div>
  </div>
</section>
@endsection
