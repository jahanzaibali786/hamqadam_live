@extends('frontend.layouts.app')
@section('meta_title', $data['meta_title'])
@section('meta_description', $data['meta_description'])
@section('meta')
<link rel="canonical" href="{{ url('/refund-policy') }}">
@if(!empty($preview))<meta name="robots" content="noindex,nofollow">@endif
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-legal-pages-20261120.css') }}?v=1">
@endsection
@section('content')
<main class="hql-legal-page">
  <div class="hql-wrap">
    @if(!empty($preview))
      <div class="hql-preview" role="status">ADMIN PREVIEW — Not publicly published</div>
    @endif
    <header class="hql-header">
      <span class="hql-kicker"><i class="las la-shield-alt" aria-hidden="true"></i> Hamqadam App (Private) Limited</span>
      <h1>{{ $data['title'] }}</h1>
      <p>Clear information about subscriptions, refunds, cancellations and payment support.</p>
    </header>
    <div class="hql-turnaround" aria-label="Refund processing commitment">
      <div class="hql-turnaround-number">7 <small>Working Days</small></div>
      <div class="hql-turnaround-copy">
        <strong>{{ $data['turnaround_heading'] }}</strong>
        <span>After verification and approval, eligible refunds are initiated within seven working days.</span>
      </div>
    </div>
    <div class="hql-columns">
      <nav class="hql-toc" aria-label="Refund policy contents">
        <strong>On this page</strong>
        @foreach($data['sections'] as $i=>$section)
          <a href="#refund-section-{{ $i+1 }}">{{ $section['heading'] }}</a>
        @endforeach
        <a href="#refund-timeline">Refund Processing Time</a>
      </nav>
      <div class="hql-document">
        @foreach($data['sections'] as $i=>$section)
          <section id="refund-section-{{ $i+1 }}" class="hql-section">
            <h2>{{ $section['heading'] }}</h2>
            <div class="hql-rich">{!! $section['body'] !!}</div>
          </section>
        @endforeach
        <section id="refund-timeline" class="hql-section hql-timeline">
          <h2>{{ $data['turnaround_heading'] }}</h2>
          <div class="hql-rich">{!! $data['turnaround_body'] !!}</div>
        </section>
        <div class="hql-help-cta">
          <div><h2>Need help with a payment?</h2><p>Contact our support team with your transaction reference and registered account details.</p></div>
          <a href="{{ route('contact_us') }}">Contact Support <i class="las la-arrow-right" aria-hidden="true"></i></a>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
