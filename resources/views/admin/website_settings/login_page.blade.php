@extends('admin.layouts.app')
@section('content')
@php
$groups=[
'Page & Welcome'=>['meta_title'=>'Page title','eyebrow'=>'Badge text','community_text'=>'Verification community label','helpline_caption'=>'Helpline caption'],
'Story & Testimonial'=>['story_kicker'=>'Story badge','story_context'=>'Portal label','story_title'=>'Main title (new lines supported)','story_quote'=>'Editorial quote','testimonial_name'=>'Testimonial name','testimonial_detail'=>'Testimonial subtitle','testimonial_quote'=>'Testimonial message','stat_one_value'=>'Stat 1 value','stat_one_label'=>'Stat 1 label','stat_two_value'=>'Stat 2 value','stat_two_label'=>'Stat 2 label'],
'Sign in'=>['form_kicker'=>'Form badge','form_title'=>'Form heading','email_placeholder'=>'Email placeholder','password_placeholder'=>'Password placeholder','remember_text'=>'Remember me label','session_text'=>'Security label','submit_text'=>'Submit button label','register_title'=>'Registration heading','register_description'=>'Registration description','register_button'=>'Create account label','support_prompt'=>'Help prompt','support_button'=>'Support link label'],
'Trust & Safety'=>['trust_1_title'=>'Trust item 1 title','trust_1_description'=>'Trust item 1 text','trust_2_title'=>'Trust item 2 title','trust_2_description'=>'Trust item 2 text','trust_3_title'=>'Trust item 3 title','trust_3_description'=>'Trust item 3 text','trust_4_title'=>'Trust item 4 title','trust_4_description'=>'Trust item 4 text'],
'Image Cards'=>['card_1_title'=>'Ceremony caption','card_1_alt'=>'Ceremony alternative text','card_2_title'=>'Family caption','card_2_alt'=>'Family alternative text','card_3_title'=>'Couple caption','card_3_alt'=>'Couple alternative text']
];
$defaults=[
'meta_title'=>'Login to Hamqadam','eyebrow'=>'CHOOSE FOREVER • SANCTUARY OF DIGNIFIED MATRIMONY','community_text'=>'100% ID Verified Community','helpline_caption'=>'Family Helpline:',
'story_kicker'=>'RISHTON KI DUNIYA','story_context'=>'Member Portal','story_title'=>"A sacred union\nbuilt upon\nsincerity & trust.",'story_quote'=>'Every lasting union begins with respectful intention. Step back into the sanctuary where values, family honor, and genuine companionship unite.',
'testimonial_name'=>'Dr. Arsalan & Mahnoor','testimonial_detail'=>'Matched in Islamabad • Married Oct 2025','testimonial_quote'=>'Hamqadam gave our families peace of mind through verification and genuine respect.','stat_one_value'=>'18,400+','stat_one_label'=>'Solemnized Unions','stat_two_value'=>'100%','stat_two_label'=>'NADRA / CNIC Verified',
'form_kicker'=>'SECURE GATEWAY','form_title'=>'Login to your Account','email_placeholder'=>'name@hamqadam.com','password_placeholder'=>'Enter your confidential password','remember_text'=>'Keep me signed in on this secure device','session_text'=>'Encrypted Session','submit_text'=>'Sign In to Hamqadam','register_title'=>'New to Hamqadam?','register_description'=>'Register now & receive 25 Welcome Coins upon identity verification.','register_button'=>'Create Account','support_prompt'=>'Need elder or guardian guidance during sign-in?','support_button'=>'Direct Family Support Desk',
'trust_1_title'=>'100% CNIC Verified','trust_1_description'=>'Biometric-matched government credentials. No pseudonyms or artificial profiles.','trust_2_title'=>'256-Bit Protection','trust_2_description'=>'Bank-grade data security. Your contact details stay shielded.','trust_3_title'=>'Dignified Purpose','trust_3_description'=>'Built for respectful matrimony and nikah.','trust_4_title'=>'Family-Led Consult','trust_4_description'=>'Dedicated support for parents and guardians.',
'card_1_title'=>'Guaranteed Privacy Protocol','card_1_alt'=>'Wedding ceremony','card_2_title'=>'Family-First Involvement','card_2_alt'=>'Traditional family gathering','card_3_title'=>'18,400+ Blessed Unions','card_3_alt'=>'Newlywed couple'
];
$images=['testimonial_image'=>'Testimonial portrait','card_1_image'=>'Ceremony image','card_2_image'=>'Family gathering image','card_3_image'=>'Couple image'];
@endphp
<div class="row"><div class="col-xl-10 mx-auto">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
<div><h4>Member Login Page</h4><p class="text-muted">Manage the login page text and photos without changing authentication, header, or footer.</p></div>
<a class="btn btn-soft-primary" href="{{ route('login') }}" target="_blank" rel="noopener">Preview login <i class="las la-external-link-alt"></i></a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form action="{{ route('website.login_page.update') }}" method="POST">
@csrf
@foreach($groups as $group=>$fields)
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ $group }}</h5></div><div class="card-body"><div class="row">
@foreach($fields as $key=>$label)
<div class="form-group col-lg-6"><label for="hq-{{ $key }}">{{ $label }}</label>
@if(in_array($key,['story_title','story_quote','testimonial_quote','register_description']) || str_ends_with($key,'_description'))
<textarea class="form-control" id="hq-{{ $key }}" name="{{ $key }}" rows="3" maxlength="1200">{{ old($key,get_setting('hq_login_'.$key,$defaults[$key]??'')) }}</textarea>
@else
<input class="form-control" id="hq-{{ $key }}" name="{{ $key }}" maxlength="1200" value="{{ old($key,get_setting('hq_login_'.$key,$defaults[$key]??'')) }}">
@endif
@error($key)<small class="text-danger">{{ $message }}</small>@enderror
</div>
@endforeach
</div></div></div>
@endforeach
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">Images</h5></div><div class="card-body"><p class="text-muted">Upload or select images to match your screenshot. The initial repository photos are placeholders until you upload the supplied three images through the admin uploader.</p><div class="row">
@foreach($images as $key=>$label)
<div class="form-group col-lg-6"><label>{{ $label }}</label><div class="input-group" data-toggle="aizuploader" data-type="image"><div class="input-group-prepend"><div class="input-group-text">Browse</div></div><div class="form-control file-amount">Choose image</div><input type="hidden" name="{{ $key }}" class="selected-files" value="{{ old($key,get_setting('hq_login_'.$key,0)) }}"></div><div class="file-preview box"></div>@error($key)<small class="text-danger">{{ $message }}</small>@enderror</div>
@endforeach
</div></div></div>
<div class="text-right mb-5"><button type="submit" class="btn btn-primary px-5">Save Login Page</button></div>
</form></div></div>
@endsection