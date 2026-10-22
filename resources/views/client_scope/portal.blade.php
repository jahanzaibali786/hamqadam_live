@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $topMatch=$matches->first();
 $person=$topMatch?->matchedUser;
 $self=auth()->user();
 $compat=$topMatch ? max(0,min(100,(int)round($topMatch->match_percentage))) : 0;
 $breakdown=collect($topMatch?->score_breakdown ?? [])->filter(fn($value)=>is_numeric($value))->take(6);
 $reasons=collect($topMatch?->compatibility_reasons ?? [])->filter(fn($reason)=>is_string($reason)&&trim($reason)!=='')->take(5);
 $shared=collect($topMatch?->mutual_matched_preferences ?? [])->filter(fn($value)=>is_string($value)&&trim($value)!=='')->take(8);
 $partner=$person?->member;
 $presentAddress=$person ? \App\Models\Address::where('user_id',$person->id)->where('type','present')->with('city','country')->first() : null;
 $selfAddress=\App\Models\Address::where('user_id',$self->id)->where('type','present')->with('city','country')->first();
 $partnerFallback=static_asset(($partner?->gender == 1)?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png');
 $partnerPhoto=$person && show_profile_picture($person) && $person->photo ? uploaded_asset($person->photo) : $partnerFallback;
 $myPhoto=$self->photo ? uploaded_asset($self->photo) : static_asset('assets/img/avatar-place.png');
 $canView=$person && (get_setting('full_profile_show_according_to_membership') != 1 || \App\Models\ProfileViewer::where('user_id',$person->id)->where('viewed_by',$self->id)->exists() || (package_validity($self->id) && get_remaining_package_value($self->id,'remaining_profile_viewer_view')>0));
 $interestSent=$person ? \App\Models\ExpressInterest::where('user_id',$person->id)->where('interested_by',$self->id)->first() : null;
 $interestReceived=$person ? \App\Models\ExpressInterest::where('user_id',$self->id)->where('interested_by',$person->id)->first() : null;
 $reported=$person ? \App\Models\ReportedUser::where('user_id',$person->id)->where('reported_by',$self->id)->exists() : false;
 $shortlisted=$person ? \App\Models\Shortlist::where('user_id',$person->id)->where('shortlisted_by',$self->id)->exists() : false;
 $accepted=($interestSent && (int)$interestSent->status->value===1)||($interestReceived && (int)$interestReceived->status->value===1);
@endphp
<div class="hm-2026 hm-match-2026">
 <header class="hm-2026-banner"><div class="hm-2026-heading"><span class="hm-2026-heading-icon"><i class="las la-magic"></i></span><div><h1>Why This Match?</h1><p>Discover why you and {{ $person?->first_name ?? 'your top match' }} could be a great match.</p></div></div><div class="hm-2026-banner-note"><i class="las la-heart"></i><strong>Shared values create stronger connections.</strong></div></header>
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 @if(!$person)
 <section class="hm-match-empty"><i class="las la-heart"></i><h2>No compatible match available yet</h2><p>Complete your partner preferences or refresh your matching results. This page shows one highest-scoring eligible match when available.</p><a href="{{ route('my_matched_profiles') }}" class="hm-pink-button">Explore Matched Profiles</a></section>
 @else
 <div class="hm-match-hero">
  <section class="hm-match-person"><img src="{{ $partnerPhoto }}" onerror="this.onerror=null;this.src='{{ $partnerFallback }}'" alt="{{ $person->first_name }} profile"><div><h2>{{ $person->first_name }} {{ $person->last_name }} @if(app(\App\Services\BadgeService::class)->isVerified($partner))<i class="las la-check-circle hm-verified" title="Verified"></i>@endif</h2><p>{{ $partner?->birthday ? \Carbon\Carbon::parse($partner->birthday)->age.' years · ' : '' }}{{ $presentAddress?->city?->name ?? $presentAddress?->country?->name ?? 'Location not specified' }}</p><ul><li><i class="las la-briefcase"></i>{{ $partner?->job_title ?: 'Profession not specified' }}</li><li><i class="las la-map-marker"></i>{{ $presentAddress?->city?->name ?? 'City not specified' }}</li></ul>@if($canView)<a href="{{ route('member_profile',$person->id) }}">View Profile <i class="las la-arrow-right"></i></a>@else<a href="{{ route('packages') }}">Upgrade to view profile <i class="las la-arrow-right"></i></a>@endif</div></section>
  <section class="hm-match-score"><div class="hm-match-circle" style="--score:{{ $compat }}"><i class="las la-heart"></i><strong>{{ $compat }}%</strong></div><h2>{{ $compat>=80?'High':($compat>=60?'Good':'Potential') }} Compatibility</h2><p>{{ $topMatch->compatibility_explanation ?: 'Your match is based on available profile details and your partner preferences.' }}</p>
  @if($interestReceived)<a class="hm-pink-button" href="{{ route('interest_requests') }}">Respond to Interest <i class="las la-arrow-right"></i></a>
  @elseif($interestSent)<span class="hm-match-sent">{{ $accepted?'Interest Accepted':'Interest Sent' }}</span>
  @else<button class="hm-pink-button" type="button" onclick="hmMatchInterest({{ $person->id }})">Send Interest <i class="las la-arrow-right"></i></button>@endif
  </section>
  <section class="hm-match-person hm-match-self"><img src="{{ $myPhoto }}" alt="Your profile photo"><div><h2>{{ $self->first_name }} {{ $self->last_name }}</h2><p>{{ $self->member?->birthday ? \Carbon\Carbon::parse($self->member->birthday)->age.' years · ' : '' }}{{ $selfAddress?->city?->name ?? 'Location not specified' }}</p><ul><li><i class="las la-briefcase"></i>{{ $self->member?->job_title ?: 'Profession not specified' }}</li><li><i class="las la-map-marker"></i>{{ $selfAddress?->city?->name ?? 'City not specified' }}</li></ul><a href="{{ route('member_profile',$self->id) }}">My Public Profile <i class="las la-arrow-right"></i></a></div></section>
  <aside class="hm-match-quick"><h2>Quick Actions</h2>
   @if($interestSent)<span><i class="las la-heart"></i>{{ $accepted?'Interest Accepted':'Interest Sent' }}</span>@elseif($interestReceived)<a href="{{ route('interest_requests') }}"><i class="las la-heart"></i>Respond to Interest</a>@else<button type="button" onclick="hmMatchInterest({{ $person->id }})"><i class="las la-heart"></i>Send Interest</button>@endif
   <a href="{{ route('all.messages') }}"><i class="las la-comment-dots"></i>Messaging</a>
   @if($shortlisted)<span><i class="las la-bookmark"></i>Already Shortlisted</span>@elseif($accepted)<button type="button" onclick="hmMatchShortlist({{ $person->id }})"><i class="las la-bookmark"></i>Add to Shortlist</button>@else<span title="Shortlisting is available after mutual interest"><i class="las la-bookmark"></i>Shortlist after acceptance</span>@endif
   <button type="button" id="hm-match-share"><i class="las la-share-alt"></i>Share Profile</button>
   @if(!$reported)<button type="button" onclick="hmMatchReport({{ $person->id }})"><i class="las la-flag"></i>Report Profile</button>@else<span><i class="las la-flag"></i>Reported</span>@endif
   <button type="button" onclick="hmMatchIgnore({{ $person->id }})"><i class="las la-ban"></i>Block / Ignore User</button>
  </aside>
 </div>
 <nav class="hm-match-tabs" aria-label="Compatibility analysis">
  <button class="is-active" data-match-tab="overview">Compatibility Overview</button><button data-match-tab="values">Shared Values</button><button data-match-tab="lifestyle">Lifestyle Match</button><button data-match-tab="background">Family &amp; Background</button><button data-match-tab="education">Education &amp; Career</button><button data-match-tab="detail">Detailed Analysis</button>
 </nav>
 <div class="hm-match-insights" data-match-panel="overview">
  <section class="hm-match-card"><h2>Key Matching Factors</h2>
   @forelse($breakdown as $label=>$score)<div class="hm-match-factor"><span>{{ \Illuminate\Support\Str::headline((string)$label) }}</span><div class="hm-match-track"><b style="width:{{ max(0,min(100,(float)$score)) }}%"></b></div><strong>{{ round($score) }}%</strong></div>
   @empty<p class="hm-match-muted">Detailed category scores are not available for this match.</p>@endforelse
  </section>
  <section class="hm-match-card"><h2>AI Match Summary @if($topMatch->ai_enhanced)<small>AI enhanced</small>@endif</h2><p class="hm-match-highlight">{{ $topMatch->compatibility_explanation ?: 'Compatibility is calculated from available user preferences.' }}</p><h3>Top Reasons for This Match</h3><ol>@forelse($reasons as $reason)<li>{{ $reason }}</li>@empty<li>No detailed reasons were recorded for this match.</li>@endforelse</ol></section>
  <section class="hm-match-card"><h2>Additional Insights</h2><p><strong>Mutual Preferences</strong></p><div class="hm-match-chips">@forelse($shared as $preference)<span>{{ $preference }}</span>@empty<p class="hm-match-muted">Detailed shared preferences are not yet available.</p>@endforelse</div><hr><p><strong>Location Compatibility</strong></p><p class="hm-match-muted">{{ $selfAddress?->city?->name && $presentAddress?->city?->name && $selfAddress->city->name===$presentAddress->city->name ? 'You are both in the same city.' : 'Check each other’s current location and travel preferences.' }}</p></section>
 </div>
 <div class="hm-match-info-panel" data-match-panel="values" hidden><h2>Shared Values</h2><div class="hm-match-chips">@forelse($shared as $preference)<span>{{ $preference }}</span>@empty<p>Mutual preference details have not yet been recorded.</p>@endforelse</div></div>
 <div class="hm-match-info-panel" data-match-panel="lifestyle" hidden><h2>Lifestyle Compatibility</h2><p>Review lifestyle preferences in the detailed member profile. The match engine uses stored profile preferences.</p><a href="{{ $canView?route('member_profile',$person->id):route('packages') }}">View available profile details</a></div>
 <div class="hm-match-info-panel" data-match-panel="background" hidden><h2>Family &amp; Background</h2><p>Family information is available through the member profile subject to its existing privacy settings.</p></div>
 <div class="hm-match-info-panel" data-match-panel="education" hidden><h2>Education &amp; Career</h2><p>Explore education and professional background in the member’s profile, where permitted.</p></div>
 <div class="hm-match-info-panel" data-match-panel="detail" hidden><h2>Detailed Analysis</h2><p>{{ $topMatch->compatibility_explanation ?: 'No expanded analysis is available for this match.' }}</p><h3>Suggested Next Steps</h3><ul>@forelse((array)($topMatch->recommended_actions ?? []) as $action)<li>{{ is_scalar($action)?$action:'Further conversation may help clarify shared goals.' }}</li>@empty<li>Review this member’s profile and communicate respectfully.</li>@endforelse</ul></div>
 <div class="hm-match-footer"><span>Other compatible people are listed separately under <strong>Matched Profiles</strong>.</span><a href="{{ route('my_matched_profiles') }}">View All Matched Profiles <i class="las la-arrow-right"></i></a></div>
 @endif
</div>
@endsection
@section('script')
<script>
(function(){
 const root=document.querySelector('.hm-match-2026');if(!root)return;
 const tabs=root.querySelectorAll('[data-match-tab]');
 tabs.forEach(tab=>tab.addEventListener('click',()=>{tabs.forEach(x=>x.classList.toggle('is-active',x===tab));root.querySelectorAll('[data-match-panel]').forEach(x=>x.hidden=x.dataset.matchPanel!==tab.dataset.matchTab);}));
 root.querySelector('#hm-match-share')?.addEventListener('click',()=>{
  const url=@json($person?route('member_profile',$person->id):route('my_matched_profiles'));
  if(navigator.share)navigator.share({title:'Hamqadam Member',url}).catch(()=>{});
  else if(navigator.clipboard)navigator.clipboard.writeText(url).then(()=>alert('Profile link copied')).catch(()=>prompt('Copy profile link',url));
  else prompt('Copy profile link',url);
 });
})();
function hmMatchInterest(id){
 @if($person)
 const btn=document.querySelector('.hm-match-2026 .hm-match-score button'); if(btn)btn.disabled=true;
 const canPurchase=@json(package_validity(auth()->id()));
 if(!canPurchase){location.assign(@json(route('packages')));return;}
 const token=@json(csrf_token());
 fetch(@json(route('user.remaining_package_value')),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':token,'Accept':'application/json'},body:new URLSearchParams({_token:token,id:@json(auth()->id()),colmn_name:'remaining_interest'})})
 .then(r=>{if(!r.ok)throw new Error('Balance unavailable');return r.text();})
 .then(value=>{
  const available=Number(value.replace(/[^0-9.-]/g,''));if(!Number.isFinite(available)||available<1){location.assign(@json(route('packages')));return;}
  if(!confirm('Send interest? This action costs 1 coin.')){if(btn)btn.disabled=false;return;}
  return fetch(@json(route('express-interest.store')),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':token,'Accept':'application/json'},body:new URLSearchParams({_token:token,id:String(id)})}).then(r=>{if(!r.ok)throw new Error('Unable to send interest');return r.text()}).then(()=>location.reload());
 }).catch(()=>{if(btn)btn.disabled=false;alert('Could not process your interest. Please try again.');});
 @endif
}
function hmMatchShortlist(id){@if($person) const btn=document.getElementById('shortlist_a_id_'+id); if(btn)btn.click();else location.assign(@json(route('member_profile',$person->id))); @endif}
function hmMatchReport(id){@if($person)location.assign(@json(route('member_profile',$person->id)));@endif}
function hmMatchIgnore(id){@if($person)location.assign(@json(route('member_profile',$person->id)));@endif}
</script>
@endsection