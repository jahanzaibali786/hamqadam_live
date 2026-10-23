@extends('frontend.layouts.app')
@section('meta_title', get_setting('hq_login_meta_title', 'Login to Hamqadam'))
@section('meta')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-login.css') }}?v=20261023">
@endsection
@section('content')
@php
    $content = fn($key,$fallback='') => get_setting('hq_login_'.$key,$fallback);
    $photo = function($slot,$fallback) {
        $id=(int)get_setting('hq_login_'.$slot,0);
        return $id && uploaded_asset($id) ? uploaded_asset($id) : static_asset($fallback);
    };
    $cards = [
       ['ceremony','Wedding Ceremony','Guaranteed Privacy Protocol','assets/img/hamqadam-luxury/hero-jewelry.jpg'],
       ['family','Family Gathering','Family-First Involvement','assets/img/bg.jpg'],
       ['couple','Wedding Couple','18,400+ Blessed Unions','assets/img/hamqadam-luxury/hero-jewelry.jpg'],
    ];
    $trust = [
       ['fingerprint','100% CNIC Verified','Biometric-matched government credentials. No pseudonyms or artificial profiles.'],
       ['lock','256-Bit Protection','Bank-grade data security. Your contact details stay shielded.'],
       ['users','Dignified Purpose','Built for respectful matrimony and nikah.'],
       ['user-friends','Family-Led Consult','Dedicated support for parents and guardians.'],
    ];
@endphp
<div class="hq-login-page">
 <div class="hq-login-container">
  <div class="hq-login-intro">
   <span class="hq-login-badge"><span class="hq-login-dot"></span>{{ $content('eyebrow','CHOOSE FOREVER • SANCTUARY OF DIGNIFIED MATRIMONY') }}</span>
   <div class="hq-login-topinfo"><span><i class="las la-certificate"></i> {{ $content('community_text','100% ID Verified Community') }}</span><span class="hq-login-sep">•</span><a href="tel:{{ preg_replace('/[^+0-9]/','',get_setting('header_helpline_no','+01112352566')) }}"><i class="las la-headset"></i> {{ $content('helpline_caption','Family Helpline:') }} <strong>{{ get_setting('header_helpline_no','+01 112 352 566') }}</strong></a></div>
  </div>
  <div class="hq-login-columns">
   <section class="hq-login-story">
    <div class="hq-login-story-kicker"><span>{{ $content('story_kicker','RISHTON KI DUNIYA') }}</span><b>•</b>{{ $content('story_context','Member Portal') }}</div>
    <h1>{!! nl2br(e($content('story_title',"A sacred union\nbuilt upon\nsincerity & trust."))) !!}</h1>
    <p class="hq-login-quote">“{{ $content('story_quote','Every lasting union begins with respectful intention. Step back into the sanctuary where values, family honor, and genuine companionship unite.') }}”</p>
    <div class="hq-login-testimonial">
     <div class="hq-login-author"><img src="{{ $photo('testimonial_image','assets/img/hamqadam-luxury/hero-jewelry.jpg') }}" alt="" loading="lazy"><div><strong>{{ $content('testimonial_name','Dr. Arsalan & Mahnoor') }}</strong><small>{{ $content('testimonial_detail','Matched in Islamabad • Married Oct 2025') }}</small></div></div>
     <p>“{{ $content('testimonial_quote','Hamqadam gave our families peace of mind through verification and genuine respect.') }}”</p>
    </div>
    <div class="hq-login-stats"><div><strong>{{ $content('stat_one_value','18,400+') }}</strong><small>{{ $content('stat_one_label','Solemnized Unions') }}</small></div><div><strong>{{ $content('stat_two_value','100%') }}</strong><small>{{ $content('stat_two_label','NADRA / CNIC Verified') }}</small></div></div>
   </section>
   <section class="hq-login-panel">
    <div class="hq-login-panel-heading"><span>{{ $content('form_kicker','SECURE GATEWAY') }}</span><h2>{{ $content('form_title','Login to your Account') }}</h2></div>
    @if(session('ai_verification_notice'))<div class="hq-login-alert" role="status">{{ session('ai_verification_notice') }}</div>@endif
    @if($errors->any())<div class="hq-login-alert hq-login-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login') }}" class="hq-login-form">
     @csrf
     <div class="hq-login-field"><label for="hq_login_email">{{ addon_activation('otp_system') ? translate('Email / Phone') : translate('Email') }}</label><div class="hq-login-input"><i class="las la-envelope-open"></i><input id="hq_login_email" name="email" type="{{ addon_activation('otp_system') ? 'text' : 'email' }}" value="{{ old('email') }}" autocomplete="username" placeholder="{{ $content('email_placeholder','name@hamqadam.com') }}" required aria-invalid="{{ $errors->has('email')?'true':'false' }}"></div>@error('email')<small class="hq-login-field-error">{{ $message }}</small>@enderror</div>
     <div class="hq-login-field"><div class="hq-login-label-row"><label for="hq_login_password">{{ translate('Password') }}</label><a href="{{ route('password.request') }}">{{ translate('Forgot Password?') }}</a></div><div class="hq-login-input"><i class="las la-lock"></i><input type="password" id="hq_login_password" name="password" autocomplete="current-password" placeholder="{{ $content('password_placeholder','Enter your confidential password') }}" required aria-invalid="{{ $errors->has('password')?'true':'false' }}"><button type="button" class="hq-login-toggle" id="hq-login-toggle" aria-label="Show password" aria-pressed="false"><i class="las la-eye"></i></button></div>@error('password')<small class="hq-login-field-error">{{ $message }}</small>@enderror</div>
     <div class="hq-login-remember"><label><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>{{ $content('remember_text','Keep me signed in on this secure device') }}</span></label><span><i class="las la-lock"></i> {{ $content('session_text','Encrypted Session') }}</span></div>
     <button type="submit" class="hq-login-submit">{{ $content('submit_text','Sign In to Hamqadam') }} <i class="las la-arrow-right"></i></button>
    </form>
    <div class="hq-login-register"><div class="hq-login-register-icon"><i class="las la-star-of-life"></i></div><div class="hq-login-register-copy"><h3>{{ $content('register_title','New to Hamqadam?') }}</h3><p>{{ $content('register_description','Register now & receive 25 Welcome Coins upon identity verification.') }}</p></div><a href="{{ route('register') }}">{{ $content('register_button','Create Account') }}</a></div>
    <div class="hq-login-help"><span>{{ $content('support_prompt','Need elder or guardian guidance during sign-in?') }}</span><a href="{{ route('contact_us') }}"><i class="las la-phone"></i> {{ $content('support_button','Direct Family Support Desk') }}</a></div>
   </section>
  </div>
  <div class="hq-login-trust">
   @foreach($trust as $i=>$item)<article><div class="hq-login-trust-icon"><i class="las la-{{ $item[0] }}"></i></div><div><h3>{{ $content('trust_'.($i+1).'_title',$item[1]) }}</h3><p>{{ $content('trust_'.($i+1).'_description',$item[2]) }}</p></div></article>@endforeach
  </div>
  <div class="hq-login-gallery">
   @foreach($cards as $i=>$card)<article><img src="{{ $photo('card_'.($i+1).'_image',$card[3]) }}" alt="{{ $content('card_'.($i+1).'_alt',$card[1]) }}" loading="lazy"><div class="hq-login-gallery-shade"></div><h3>{{ $content('card_'.($i+1).'_title',$card[2]) }}</h3></article>@endforeach
  </div>
 </div>
</div>
@endsection
@section('script')
<script>
(function(){const btn=document.getElementById('hq-login-toggle'),input=document.getElementById('hq_login_password');if(!btn||!input)return;btn.addEventListener('click',()=>{const reveal=input.type==='password';input.type=reveal?'text':'password';btn.setAttribute('aria-pressed',String(reveal));btn.setAttribute('aria-label',reveal?'Hide password':'Show password');btn.querySelector('i').className=reveal?'las la-eye-slash':'las la-eye';});})();
</script>
@endsection