@extends('frontend.layouts.app')
@section('meta_title', 'Help Center | Hamqadam')
@section('meta_description', 'Hamqadam Help Center: answers to questions about accounts, safety, subscriptions and payments.')
@section('meta')
<link rel="canonical" href="{{ url('/help-center') }}">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-legal-pages-20261120.css') }}?v=1">
@endsection
@section('content')
<main class="hql-legal-page">
 <div class="hql-wrap">
   @if(!empty($preview))<div class="hql-preview">ADMIN PREVIEW — Draft category arrangement</div>@endif
   <header class="hql-header">
     <span class="hql-kicker">Help & Support</span>
     <h1>How can we help you?</h1>
     <p>Browse our help articles, answers to common questions and account support information.</p>
   </header>
   <div class="hql-help-categories">
     @foreach($categories as $category)
       <a href="#help-{{ \Illuminate\Support\Str::slug($category) }}">{{ $category }}</a>
     @endforeach
   </div>
   @forelse($categories as $category)
     <section class="hql-help-group" id="help-{{ \Illuminate\Support\Str::slug($category) }}">
       <h2>{{ $category }}</h2>
       <div class="hql-help-grid">
         @forelse($articles as $item)
           @if(($item['data']['category'] ?? 'General') === $category)
             <a class="hql-help-card" href="{{ route('help_center.article', $item['slug']) }}">
               <span>{{ !empty($item['data']['is_faq']) ? 'FAQ' : 'Article' }}</span>
               <h3>{{ $item['data']['title'] }}</h3>
               <p>{{ $item['data']['description'] }}</p>
               <i class="las la-arrow-right" aria-hidden="true"></i>
             </a>
           @endif
         @empty
           <p>New help articles will be published here.</p>
         @endforelse
       </div>
     </section>
   @empty
     <p>Help articles are being prepared.</p>
   @endforelse
   <div class="hql-help-cta"><div><h2>Still need assistance?</h2><p>Send a message to the Hamqadam support team.</p></div><a href="{{ route('contact_us') }}">Contact Support</a></div>
 </div>
</main>
@endsection
