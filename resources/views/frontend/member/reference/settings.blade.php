@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $u=auth()->user();$pct=max(0,min(100,(int)($member?->profile_completion_percentage??0)));
 $photo=$u->photo?uploaded_asset($u->photo):static_asset('assets/img/avatar-place.png');
@endphp
<div class="hm-next hm-settings-next">
<header class="hm-next-head"><div class="hm-next-heading"><i class="las la-cog"></i><div><h1>Settings &amp; Account Management</h1><p>Manage your account, privacy, security and preferences.</p></div></div><div class="hm-next-quote">Your privacy, your choice.<br>A safer experience for meaningful connections.</div></header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<nav class="hm-next-tabs" role="tablist" aria-label="Settings categories">
@foreach([['account','Account Settings','la-user'],['privacy','Privacy & Visibility','la-shield-alt'],['notifications','Notifications','la-bell'],['security','Security','la-lock'],['appearance','Language & Appearance','la-globe'],['blocked','Blocked Users','la-ban'],['actions','Account Actions','la-user-shield']] as $t)
<button type="button" role="tab" id="hm-settings-tab-{{ $t[0] }}" aria-controls="hm-settings-panel-{{ $t[0] }}" aria-selected="{{ $loop->first?'true':'false' }}" tabindex="{{ $loop->first?'0':'-1' }}" data-settings-tab="{{ $t[0] }}"><i class="las {{ $t[2] }}"></i>{{ $t[1] }}</button>
@endforeach
</nav>
<div class="hm-settings-top" data-settings-panel="account" role="tabpanel" id="hm-settings-panel-account" aria-labelledby="hm-settings-tab-account">
<section class="hm-next-panel" id="account"><div class="hm-next-section-title"><h2><i class="las la-user-cog"></i> Account Settings</h2><a class="hm-next-primary" href="{{ route('profile_settings') }}">Edit Profile →</a></div><p>Manage your basic account information and preferences.</p>
<div class="hm-settings-fields"><label>Full Name<input value="{{ trim($u->first_name.' '.$u->last_name) }}" readonly></label><label>Email Address<input value="{{ $u->email }}" readonly></label><label>Gender<input value="{{ $member?->gender==1?'Male':($member?->gender==2?'Female':'Not provided') }}" readonly></label><label>Date of Birth<input value="{{ $member?->birthday??'Not provided' }}" readonly></label><label>Marital Status<input value="{{ $member?->marital_status?->name??'Not provided' }}" readonly></label><label>Country<input value="{{ $address?->country?->name??'Not provided' }}" readonly></label></div>
</section>
<section class="hm-next-panel hm-settings-preview"><div class="hm-next-section-title"><h2>Profile Preview</h2><a href="{{ route('member_profile',$u->id) }}">View Public Profile</a></div><div><img src="{{ $photo }}" alt="Member profile"><div><h3>{{ $u->first_name }} {{ $u->last_name }}</h3><small>{{ $address?->city?->name??'Location not provided' }}</small><small>{{ $member?->job_title??'Profession not provided' }}</small><p>{{ \Illuminate\Support\Str::limit((string)($member?->introduction??'Add a short introduction to help others know you.'),100) }}</p><a href="{{ route('profile_settings') }}">Read More →</a></div></div></section>
</div>
<div class="hm-settings-cards">
<section class="hm-next-panel" id="hm-settings-panel-privacy" role="tabpanel" aria-labelledby="hm-settings-tab-privacy" data-settings-panel="privacy" hidden><h2><i class="las la-eye"></i> Privacy &amp; Visibility</h2><p>Control who can see your profile and information.</p><form method="POST" action="{{ route('member.reference.settings.privacy') }}">@csrf
@foreach([['hide_profile','Hide my profile from discovery',(bool)($member?->hide_profile??false)],['show_photo','Show profile photo',(bool)($privacy->show_photo??true)],['show_location','Show location',(bool)($privacy->show_location??true)],['allow_profile_view_notifications','Notify me about profile views',(bool)($privacy->allow_profile_view_notifications??true)],['do_not_disturb','Do Not Disturb',(bool)($privacy->do_not_disturb??false)]] as $r)<label class="hm-settings-toggle">{{ $r[1] }}<input type="checkbox" name="{{ $r[0] }}" value="1" @checked($r[2])><span></span></label>@endforeach<button type="submit" class="hm-next-primary">Save Privacy Settings</button></form></section>
<section class="hm-next-panel" id="hm-settings-panel-notifications" role="tabpanel" aria-labelledby="hm-settings-tab-notifications" data-settings-panel="notifications" hidden><h2><i class="las la-bell"></i> Notification Preferences</h2><p>Choose which notification channels to receive.</p><form method="POST" action="{{ route('member.reference.settings.notifications') }}">@csrf
@foreach([['push_enabled','Push Notifications',(bool)($notifications->push_enabled??true)],['email_enabled','Email Notifications',(bool)($notifications->email_enabled??true)],['engagement_enabled','Engagement Reminders',(bool)($notifications->engagement_enabled??true)]] as $r)<label class="hm-settings-toggle">{{ $r[1] }}<input type="checkbox" name="{{ $r[0] }}" value="1" @checked($r[2])><span></span></label>@endforeach<button type="submit" class="hm-next-primary">Save Notifications</button></form><a href="{{ route('client.scope.notifications') }}">Advanced preferences &amp; quiet hours →</a></section>
<section class="hm-next-panel" id="hm-settings-panel-security" role="tabpanel" aria-labelledby="hm-settings-tab-security" data-settings-panel="security" hidden><h2><i class="las la-lock"></i> Security &amp; Login</h2><p>Keep your account safe and secure.</p><div class="hm-next-links"><a href="{{ route('member.change_password') }}">Change Password <i class="las la-angle-right"></i></a><a href="{{ route('member.verification') }}">Identity Verification <i class="las la-angle-right"></i></a><a href="{{ route('guardian_mode.index') }}">Guardian &amp; Family Access <i class="las la-angle-right"></i></a></div></section>
<section class="hm-next-panel" id="hm-settings-panel-appearance" role="tabpanel" aria-labelledby="hm-settings-tab-appearance" data-settings-panel="appearance" hidden><h2><i class="las la-globe"></i> Language &amp; Appearance</h2><p>Your preferred appearance for this browser.</p><div class="hm-next-links"><span>Language <b>English</b></span><label>Theme <select id="hm-next-theme"><option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option></select></label></div><small>Theme preference applies to supported member views on this device.</small></section>
<section class="hm-next-panel" id="hm-settings-panel-blocked" role="tabpanel" aria-labelledby="hm-settings-tab-blocked" data-settings-panel="blocked" hidden><h2><i class="las la-ban"></i> Blocked Users</h2><p>Manage profiles you have blocked or ignored.</p><a class="hm-next-outline" href="{{ route('my_ignored_list') }}">View Blocked Users →</a></section>
<section class="hm-next-panel" id="hm-settings-panel-actions" role="tabpanel" aria-labelledby="hm-settings-tab-actions" data-settings-panel="actions" hidden><h2><i class="las la-user-shield"></i> Account Actions</h2><p>Review account controls and available support.</p><div class="hm-next-links"><a href="{{ route('client.scope.support') }}">Request a Copy of My Data <i class="las la-angle-right"></i></a><a href="{{ route('profile_settings') }}">Deactivate Account <i class="las la-angle-right"></i></a><a href="{{ route('profile_settings') }}">Delete Account <i class="las la-angle-right"></i></a></div></section>
</div></div>
@endsection
@section('script')
<script>
(function(){
 const root=document.querySelector('.hm-settings-next');if(!root)return;
 const tabs=[...root.querySelectorAll('[data-settings-tab]')],panels=[...root.querySelectorAll('[data-settings-panel]')];
 function activate(name,focus=false){
  tabs.forEach(t=>{const on=t.dataset.settingsTab===name;t.setAttribute('aria-selected',String(on));t.tabIndex=on?0:-1;if(on&&focus)t.focus();});
  panels.forEach(p=>{p.hidden=p.dataset.settingsPanel!==name;});
  root.classList.toggle('hm-settings-account-active',name==='account');
 }
 tabs.forEach((t,i)=>{t.addEventListener('click',()=>activate(t.dataset.settingsTab));t.addEventListener('keydown',e=>{if(e.key!=='ArrowRight'&&e.key!=='ArrowLeft'&&e.key!=='Home'&&e.key!=='End')return;e.preventDefault();let j=e.key==='Home'?0:e.key==='End'?tabs.length-1:(i+(e.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;activate(tabs[j].dataset.settingsTab,true);});});
 activate(new URLSearchParams(location.search).get('section')||'account');
})();
(function(){const key='hm-member-theme',s=document.getElementById('hm-next-theme');if(!s)return;const theme=localStorage.getItem(key)||'system';s.value=theme;function apply(v){document.documentElement.dataset.hmTheme=v;}apply(theme);s.addEventListener('change',()=>{localStorage.setItem(key,s.value);apply(s.value);});})();
</script>
@endsection