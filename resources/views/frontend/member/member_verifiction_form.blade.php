@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="hm-ref-page hm-verification-reference hm-parity-verification" data-hm-screen="verification">
    <header class="hm-ref-header"><div><span class="hm-ref-kicker">Member Trust &amp; Safety</span><h1 class="hm-ref-heading">Verification Center</h1><p class="hm-ref-lead">Get verified to build trust and more genuine connections.</p></div></header>
    <div class="hm-parity-verification-steps" aria-label="Verification process">
      @foreach([['CNIC Verification','la-id-card'],['Selfie Verification','la-camera'],['Profile Photos','la-image'],['Admin Review','la-user-shield'],['Verified','la-shield-alt']] as $step)
      <div><span><i class="las {{ $step[1] }}"></i></span><b>{{ $step[0] }}</b><small>Stage {{ $loop->iteration }}</small></div>
      @endforeach
    </div>
    <div class="hm-parity-verification-layout"><main>
    <div class="hm-ref-panel"><div class="hm-ref-panel-body d-flex align-items-center" style="gap:16px"><span class="hm-ref-stat-icon"><i class="las la-shield-alt"></i></span><div><h2 class="h5 mb-1">Verify Your Identity</h2><p class="text-muted mb-0">Only submit accurate details using your own documents. Verification status is reviewed by the team.</p></div></div></div>
    <div class="aiz-titlebar mt-2 mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Member Verification')}}</h1>
        </div>
      </div>
    </div>
    <form class="" action="{{ route('member.verification_info.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0 h6">{{ translate('Verification info')}}</h4>
            </div>
            @php
                $verification_form = get_setting('verification_form');
            @endphp
            <div class="card-body">
                @foreach (json_decode($verification_form) as $key => $element)
                    @if ($element->type == 'text')
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ $element->label }} <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-10">
                                <input type="{{ $element->type }}" class="form-control mb-3" placeholder="{{ $element->label }}" name="element_{{ $key }}" required>
                            </div>
                        </div>
                    @elseif($element->type == 'file')
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ $element->label }} <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-10">
                                <div class="custom-file">
                                    <label class="custom-file-label">
                                        <input type="{{ $element->type }}" name="element_{{ $key }}" id="file-{{ $key }}" class="custom-file-input" required>
                                        <span class="custom-file-name">{{ translate('Choose file') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @elseif ($element->type == 'select' && is_array(json_decode($element->options)))
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ $element->label }} <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <select class="form-control selectpicker" data-minimum-results-for-search="Infinity" name="element_{{ $key }}" required>
                                        @foreach (json_decode($element->options) as $value)
                                            <option value="{{ $value }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @elseif ($element->type == 'multi_select' && is_array(json_decode($element->options)))
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ $element->label }} <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <select class="form-control selectpicker" data-minimum-results-for-search="Infinity" name="element_{{ $key }}[]" multiple required>
                                        @foreach (json_decode($element->options) as $value)
                                            <option value="{{ $value }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @elseif ($element->type == 'radio' && is_array(json_decode($element->options)))
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ $element->label }} <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-10">
                                <div class="mb-3">
                                    @foreach (json_decode($element->options) as $value)
                                        <div class="radio radio-inline">
                                            <input type="radio" name="element_{{ $key }}" value="{{ $value }}" id="{{ $value }}" required>
                                            <label for="{{ $value }}">{{ $value }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                <div class="text-right mt-4">
                    <button type="submit" class="btn btn-primary">{{ translate('Apply')}}</button>
                </div>
            </div>
        </div>
    </form></main>
    <aside class="hm-parity-verification-aside">
    <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2>Verification Status</h2></div><div class="hm-ref-panel-body">
      <p class="small text-muted">Your submitted information will be reviewed using the configured verification process.</p>
      @if((bool)(auth()->user()->approved??false))<div class="text-success font-weight-bold"><i class="las la-check-circle"></i> Member account approved</div>@else<div class="text-warning font-weight-bold"><i class="las la-clock"></i> Account verification under review</div>@endif
    </div></section>
    <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2>Why Get Verified?</h2></div><div class="hm-ref-panel-body"><p>✓ Build trust with genuine members</p><p>✓ Improve confidence when connecting</p><p>✓ Help protect the community</p></div></section>
    <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2>Document &amp; Selfie Guidance</h2></div><div class="hm-ref-panel-body"><p>✓ Use a clear, recent, unfiltered photo</p><p>✓ Avoid sunglasses and face coverings</p><p>✓ Keep documents readable</p><p>✓ Only submit your own information</p><a href="{{ route('client.scope.support') }}" class="btn btn-outline-primary btn-sm mt-2">Need Help?</a></div></section>
    </aside></div></div>
@endsection
