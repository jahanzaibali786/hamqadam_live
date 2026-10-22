@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $member=$user->member;
 $approved=app(\App\Services\BadgeService::class)->isVerified($member);
 $submitted=filled($user->verification_info);
 $requestStatus=$verificationRequest?->status?->value??'not_started';
 $docs=$verificationRequest?->documents??collect();
 $hasSelfie=$docs->contains(fn($d)=>in_array($d->type?->value??'', ['selfie','face'],true));
 $hasDocuments=$docs->contains(fn($d)=>in_array($d->type?->value??'', ['cnic_front','cnic_back'],true))||$submitted;
 $stages=[['CNIC Verification',$hasDocuments?'Completed':'Pending'],['Selfie Verification',$hasSelfie?'Submitted':'Pending'],['Profile Photos',$user->photo?'Available':'Pending'],['Admin Review',$requestStatus==='under_review'?'In Progress':($requestStatus==='approved'?'Completed':'Pending')],['Verified',$approved?'Completed':'Not Completed']];
 $fields=json_decode((string)get_setting('verification_form'))??[];
@endphp
<div class="hm-next hm-verify-next">
<header class="hm-next-head"><div class="hm-next-heading"><i class="las la-shield-alt"></i><div><h1>Verification Center</h1><p>Get verified to build trust and get more genuine matches.</p></div></div><div class="hm-next-quote">A verified profile<br>creates real connections.</div></header>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="hm-verify-columns"><main>
<div class="hm-verify-progress hm-next-panel">@foreach($stages as $stage)<div class="{{ $stage[1]==='Completed'?'complete':($stage[1]==='In Progress'?'active':'') }}"><span>{{ $stage[1]==='Completed'?'✓':$loop->iteration }}</span><b>{{ $stage[0] }}</b><small>{{ $stage[1] }}</small></div>@endforeach</div>
<section class="hm-next-panel hm-verify-upload"><h2><i class="las la-camera"></i> Identity &amp; Selfie Verification</h2><p>Use accurate information and clear photos. Verification decisions are made through the existing review process.</p>
@if($approved)<div class="alert alert-success">Your member verification is approved.</div>@elseif($submitted)<div class="alert alert-info">Your verification details were submitted. Status: {{ str_replace('_',' ',ucwords($requestStatus,'_')) }}. Duplicate submissions are disabled.</div>@else
<form id="hm-verify-form" method="POST" enctype="multipart/form-data" action="{{ route('member.verification_info.store') }}">@csrf
<div class="hm-verify-form">
@foreach($fields as $key=>$field)
@if(in_array($field->type??'', ['text','file'],true))<label>{{ $field->label??'Verification detail' }}<input type="{{ $field->type }}" name="element_{{ $key }}" @if($field->type==='file') accept="image/jpeg,image/png,application/pdf" @endif required></label>
@elseif(in_array($field->type??'', ['select','radio','multi_select'],true))<label>{{ $field->label??'Select option' }}<select name="element_{{ $key }}{{ $field->type==='multi_select'?'[]':'' }}" @if($field->type==='multi_select') multiple @endif required>@foreach((array)json_decode($field->options??'[]') as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach</select></label>@endif
@endforeach
</div>
<div class="hm-verify-guidance"><div class="hm-verify-camera"><i class="las la-camera"></i><strong>Upload a Clear Selfie</strong><p>Choose a recent, unfiltered image in any selfie or photo field provided above.</p></div><div><h3>Guidelines for Selfie</h3>@foreach(['Look directly at the camera','No sunglasses, caps or masks','Good lighting and a clear face','No filters or text in the image','Only use your own identity documents'] as $tip)<p><i class="las la-check-circle"></i> {{ $tip }}</p>@endforeach</div></div>
<button type="submit" class="hm-next-primary">Submit Verification Request <i class="las la-arrow-right"></i></button>
</form>
@endif
</section>
<section class="hm-next-panel"><h2>Example Photo Guidelines</h2><div class="hm-verify-examples">@foreach([['la-user-check','Face visible','good'],['la-glasses','No sunglasses','bad'],['la-hat-cowboy','No headwear obstructing face','bad'],['la-mask','No masks','bad'],['la-adjust','Avoid low light','bad'],['la-magic','No filters','bad']] as $ex)<div><i class="las {{ $ex[0] }}"></i><small class="{{ $ex[2] }}">{{ $ex[1] }}</small></div>@endforeach</div><p class="hm-next-note">The example icons are guidance, not actual submitted member photos.</p></section>
<div class="hm-verify-bottom"><a href="{{ route('dashboard') }}">← Back to Dashboard</a>@if(!$approved && $submitted)<form method="POST" action="{{ route('member.ai_verification.run') }}">@csrf<button class="hm-next-primary">Recheck Verification →</button></form>@endif</div>
</main><aside><section class="hm-next-panel"><div class="hm-next-section-title"><h2>Verification Status</h2><a href="#hm-verify-history">View History</a></div>@foreach($stages as $s)<div class="hm-verify-status"><i class="las {{ $loop->first?'la-id-card':($loop->iteration===2?'la-camera':($loop->iteration===3?'la-image':'la-shield-alt')) }}"></i><span><b>{{ $s[0] }}</b><small>{{ $s[1] }}</small></span><i class="las {{ $s[1]==='Completed'?'la-check-circle':'la-clock' }}"></i></div>@endforeach</section>
<section class="hm-next-panel hm-verify-benefits"><h2><i class="las la-crown"></i> Why Get Verified?</h2>@foreach(['Build trust with genuine members','Help keep the community safer','Show your verification badge once approved','Make your profile more trustworthy'] as $text)<p><i class="las la-check-circle"></i> {{ $text }}</p>@endforeach</section>
<section class="hm-next-panel"><h2>Need Help?</h2><div class="hm-next-links"><a href="{{ route('client.scope.support') }}">How does verification work? →</a><a href="{{ route('client.scope.support') }}">Is my information safe? →</a><a href="{{ route('client.scope.support') }}">What if verification is rejected? →</a></div></section>
<section class="hm-next-panel" id="hm-verify-history"><h2>Recent Verification Attempts</h2>@forelse($verificationAttempts as $attempt)<div class="hm-verify-status"><i class="las la-history"></i><span><b>{{ ucfirst($attempt->status) }}</b><small>{{ $attempt->created_at?->format('d M Y') }}</small></span></div>@empty<p>No previous verification attempts.</p>@endforelse</section>
</aside></div></div>
@endsection