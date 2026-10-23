@php
 $ownProfile = (int) Auth::id() === (int) $user->id;
 $name = trim($user->first_name.' '.$user->last_name);
 $member = $user->member;
 $age = $member?->birthday ? \Carbon\Carbon::parse($member->birthday)->age : null;
 $city = '';
 $address = \App\Models\Address::where('user_id',$user->id)->where('type','present')->with(['city','country'])->first();
 $city = $address?->city?->name ?? $city;
 $country = $address?->country?->name ?? '';
 $photoAllowed = $ownProfile || show_profile_picture($user);
 $placeholder = static_asset(($member?->gender==1) ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
 $photo = ($photoAllowed && $user->photo) ? uploaded_asset($user->photo) : $placeholder;
 $verified = app(\App\Services\BadgeService::class)->isVerified($member);
 $match = !$ownProfile ? \App\Models\ProfileMatch::where('user_id',Auth::id())->where('match_id',$user->id)->first() : null;
 $score = $match && Auth::user()->member?->auto_profile_match == 1 ? max(0,min(100,(int)$match->match_percentage)) : null;
 $interestSent = !$ownProfile ? \App\Models\ExpressInterest::where('user_id',$user->id)->where('interested_by',Auth::id())->first() : null;
 $interestReceived = !$ownProfile ? \App\Models\ExpressInterest::where('user_id',Auth::id())->where('interested_by',$user->id)->first() : null;
 $shortlisted = !$ownProfile && \App\Models\Shortlist::where('user_id',$user->id)->where('shortlisted_by',Auth::id())->exists();
 $accepted = ($interestSent && (int)$interestSent->status->value===1)||($interestReceived && (int)$interestReceived->status->value===1);
 $reported = !$ownProfile && \App\Models\ReportedUser::where('user_id',$user->id)->where('reported_by',Auth::id())->exists();
 $intro = $user->introduction?->introduction ?? $member?->introduction ?? '';
 $profileView = \App\Models\ProfileViewer::where('user_id',$user->id)->count();
 $education = get_setting('member_education_section') == 'on' ? \App\Models\Education::where('user_id',$user->id)->orderByDesc('is_highest_degree')->first() : null;
 $career = get_setting('member_career_section') == 'on' ? \App\Models\Career::where('user_id',$user->id)->orderByDesc('present')->first() : null;
 $galleryPrivacy = get_setting('gallery_image_privacy');
 $galleryAllowed = $ownProfile || ($galleryPrivacy !== 'only_me' && ($galleryPrivacy !== 'premium_members' || (int) Auth::user()->membership !== 1));
 if (!$galleryAllowed && $galleryPrivacy === 'only_me' && !$ownProfile) {
   $galleryAllowed = \App\Models\ViewGalleryImage::where('user_id',$user->id)->where('requested_by',Auth::id())->where('status',1)->exists();
 }
 $galleryImages = $galleryAllowed ? \App\Models\GalleryImage::where('user_id',$user->id)->latest()->limit(4)->get() : collect();
@endphp
<div class="hm-detail">
 <div class="hm-detail-back"><a href="{{ url()->previous() }}"><i class="las la-arrow-left"></i> Back to Search Results</a><span>Member ID: {{ $user->code }}</span></div>
 <header class="hm-detail-header"><div class="hm-detail-title"><span class="hm-detail-bubble"><i class="las la-heart"></i></span><div><h1>Profile Details</h1><p>Get to know more about this member.</p></div></div>
 <div class="hm-detail-head-actions">
 @if($ownProfile)<a href="{{ route('profile_settings') }}" class="hm-detail-quiet"><i class="las la-edit"></i> Edit My Profile</a>
 @else
 <button type="button" class="hm-detail-quiet" onclick="document.getElementById('shortlist_a_id_{{ $user->id }}')?.click()"><i class="las la-bookmark"></i> {{ $shortlisted?'Shortlisted':'Add to Shortlist' }}</button>
 <button type="button" class="hm-detail-quiet" onclick="report_member({{ $user->id }})" @disabled($reported)><i class="las la-flag"></i> {{ $reported?'Reported':'Report Profile' }}</button>
 <button type="button" class="hm-detail-quiet" onclick="ignore_member({{ $user->id }})"><i class="las la-ban"></i> Block User</button>
 @endif
 </div></header>
 <div class="hm-detail-columns">
 <section class="hm-detail-left">
 <div class="hm-detail-photo"><img src="{{ $photo }}" alt="{{ $name }}" onerror="this.onerror=null;this.src='{{ $placeholder }}'">
 @unless($photoAllowed)<div class="hm-detail-photo-private"><i class="las la-lock"></i> Photo private</div>@endunless
 </div>
 <div class="hm-detail-thumbnails"><span><img src="{{ $photo }}" alt="Profile picture"></span>
 @foreach($galleryImages as $galleryImage)<span><img src="{{ uploaded_asset($galleryImage->image) }}" loading="lazy" alt="Member gallery photo"></span>@endforeach
 @if($galleryImages->isEmpty())<span class="hm-detail-thumbnails-note">{{ $galleryAllowed ? 'No additional photos available' : 'Private gallery — request access below' }}</span>@endif
 </div>
 <article class="hm-detail-panel"><h2><i class="las la-id-card"></i> Basic Information</h2>
 <dl><dt>Age</dt><dd>{{ $age ? $age.' Years' : 'Not specified' }}</dd><dt>Gender</dt><dd>{{ $member?->gender==1?'Male':($member?->gender==2?'Female':'Not specified') }}</dd><dt>Marital Status</dt><dd>{{ $member?->marital_status?->name ?? 'Not specified' }}</dd><dt>City</dt><dd>{{ $city ?: 'Not specified' }}</dd><dt>Country</dt><dd>{{ $country ?: 'Not specified' }}</dd><dt>Religion</dt><dd>{{ $user->spiritual_backgrounds?->religion?->name ?? 'Not specified' }}</dd></dl></article>
 </section>
 <section class="hm-detail-center">
 <div class="hm-detail-identity"><div><h2>{{ $name }} @if($verified)<span class="hm-detail-verified"><i class="las la-check-circle"></i> Verified</span>@endif</h2><p>{{ $age ? $age.' Years · ' : '' }}{{ trim($city.', '.$country,' ,') }}</p><p><i class="las la-briefcase"></i> Member &nbsp; <i class="las la-map-marker"></i> {{ $city }}</p></div>
 @if($score !== null)<a href="{{ route('client.scope.portal') }}" class="hm-detail-score"><strong>{{ $score }}%</strong><span>Compatibility Match</span></a>@endif
 </div>
 <div class="hm-detail-actionbar">
 @if($ownProfile)<a class="hm-detail-action-primary" href="{{ route('profile_settings') }}"><i class="las la-edit"></i> Edit Profile</a>
 @else
 @if($interestReceived)<a class="hm-detail-action-primary" href="{{ route('interest_requests') }}">Respond to Interest</a>
 @elseif(!$interestSent)<button class="hm-detail-action-primary" type="button" onclick="express_interest({{ $user->id }})"><i class="las la-heart"></i> Send Interest</button>
 @else<span class="hm-detail-action-primary hm-detail-action-disabled">Interest {{ $accepted?'Accepted':'Sent' }}</span>@endif
 <a href="{{ route('all.messages') }}" class="hm-detail-action-light"><i class="las la-comment-dots"></i> Messages</a>
 <a href="{{ route('member.reference.meetings') }}" class="hm-detail-action-light"><i class="las la-calendar-check"></i> Meetings</a>
 @endif
 </div>
 <blockquote class="hm-detail-quote">“{{ \Illuminate\Support\Str::limit(strip_tags((string)$intro),180) ?: 'A meaningful connection begins with respect, kindness and trust.' }}”</blockquote>
 <article class="hm-detail-panel"><h2><i class="las la-user"></i> About Me</h2><p>{{ strip_tags((string)$intro) ?: 'No introduction has been provided yet.' }}</p></article>
 <div class="hm-detail-panels-two">
 <article class="hm-detail-panel"><h2><i class="las la-graduation-cap"></i> Education &amp; Career</h2><dl><dt>Degree</dt><dd>{{ $education?->degree ?: 'Not specified' }}</dd><dt>Institution</dt><dd>{{ $education?->institution ?: 'Not specified' }}</dd><dt>Profession</dt><dd>{{ $career?->designation ?: 'Not specified' }}</dd><dt>Organization</dt><dd>{{ $career?->company ?: 'Not specified' }}</dd></dl></article>
 <article class="hm-detail-panel"><h2><i class="las la-users"></i> Family Details</h2><p>View family details in the complete profile section below, where member visibility settings still apply.</p></article>
 <article class="hm-detail-panel"><h2><i class="las la-leaf"></i> Lifestyle</h2><dl><dt>Diet</dt><dd>{{ $user->lifestyles?->diet ?? 'Not specified' }}</dd><dt>Smoking</dt><dd>{{ $user->lifestyles?->smoke ?? 'Not specified' }}</dd><dt>Drinking</dt><dd>{{ $user->lifestyles?->drink ?? 'Not specified' }}</dd></dl></article>
 <article class="hm-detail-panel"><h2><i class="las la-map-marker"></i> Location</h2><p>{{ trim($city.', '.$country,' ,') ?: 'Not specified' }}</p></article>
 </div>

 </section>
 <aside class="hm-detail-right">
 <article class="hm-detail-panel"><h2>Quick Actions</h2>
 @if($ownProfile)<a href="{{ route('profile_settings') }}">Edit Public Profile <i class="las la-angle-right"></i></a>
 @else
 <button type="button" onclick="express_interest({{ $user->id }})" @disabled((bool)$interestSent)>Send Interest <i class="las la-heart"></i></button>
 <a href="{{ route('all.messages') }}">Start Conversation <i class="las la-comment-dots"></i></a>
 <button type="button" onclick="document.getElementById('shortlist_a_id_{{ $user->id }}')?.click()">Add to Shortlist <i class="las la-bookmark"></i></button>
 <button type="button" onclick="navigator.share?navigator.share({url:location.href,title:document.title}):navigator.clipboard?.writeText(location.href)">Share Profile <i class="las la-share-alt"></i></button>
 <button type="button" onclick="report_member({{ $user->id }})" @disabled($reported)>Report Profile <i class="las la-flag"></i></button>
 <button type="button" onclick="ignore_member({{ $user->id }})">Block User <i class="las la-ban"></i></button>
 @endif</article>
 <article class="hm-detail-panel hm-detail-boost"><h2><i class="las la-rocket"></i> Find Better Matches</h2><p>Explore membership benefits for more meaningful connections.</p><a class="hm-detail-action-primary" href="{{ route('member.reference.packages') }}">View Packages →</a></article>
 <article class="hm-detail-panel"><h2>Member Activity</h2><dl><dt>Joined</dt><dd>{{ $user->created_at?->format('d M Y') }}</dd><dt>Last updated</dt><dd>{{ $user->updated_at?->format('d M Y') }}</dd><dt>Profile views</dt><dd>{{ number_format($profileView) }}</dd></dl></article>
 </aside></div>
<section class="hm-public-visible-sections" aria-label="Complete member profile information">
 <header class="hm-public-section-heading"><span><i class="las la-id-card"></i></span><div><h2>More About {{ $ownProfile ? 'Me' : $user->first_name }}</h2><p>Explore the profile information section by section.</p></div></header>
 <div class="hm-public-feature-grid">
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-users"></i></div><h3>Family &amp; Background</h3>
   <div class="hm-public-feature-facts"><span>Family Type <b>{{ $member?->family_type ?: 'Not specified' }}</b></span><span>Family Values <b>{{ $member?->family_values ?: 'Not specified' }}</b></span><span>Brothers <b>{{ $user->families?->no_of_brothers ?? 'Not specified' }}</b></span><span>Sisters <b>{{ $user->families?->no_of_sisters ?? 'Not specified' }}</b></span></div>
   @if(filled($member?->family_bio))<p>{{ $member->family_bio }}</p>@endif
  </article>
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-heart"></i></div><h3>Personality &amp; Values</h3>
   <div class="hm-public-feature-facts"><span>Communication <b>{{ $member?->communication_style ?: 'Not specified' }}</b></span><span>Personality <b>{{ $member?->personality_type ?: 'Not specified' }}</b></span><span>Future Goals <b>{{ $member?->future_goals ?: 'Not specified' }}</b></span><span>Life Values <b>{{ $member?->life_values ?: 'Not specified' }}</b></span></div>
  </article>
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-graduation-cap"></i></div><h3>Education &amp; Career</h3>
   <div class="hm-public-feature-facts"><span>Education <b>{{ $education?->degree ?: ($member?->education_level ?: 'Not specified') }}</b></span><span>Institution <b>{{ $education?->institution ?: 'Not specified' }}</b></span><span>Career <b>{{ $career?->designation ?: 'Not specified' }}</b></span><span>Company <b>{{ $career?->company ?: 'Not specified' }}</b></span></div>
  </article>
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-moon"></i></div><h3>Religion &amp; Beliefs</h3>
   <div class="hm-public-feature-facts"><span>Religion <b>{{ $user->spiritual_backgrounds?->religion?->name ?? 'Not specified' }}</b></span><span>Sect <b>{{ $member?->sectMain?->name ?? 'Not specified' }}</b></span><span>School of Thought <b>{{ $member?->schoolOfThought?->name ?? 'Not specified' }}</b></span><span>Tradition <b>{{ $member?->tradition?->name ?? 'Not specified' }}</b></span></div>
  </article>
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-map-marked-alt"></i></div><h3>Personal &amp; Location</h3>
   <div class="hm-public-feature-facts"><span>Age <b>{{ $age ? $age.' years' : 'Not specified' }}</b></span><span>Marital Status <b>{{ $member?->marital_status?->name ?? 'Not specified' }}</b></span><span>City <b>{{ $city ?: 'Not specified' }}</b></span><span>Country <b>{{ $country ?: 'Not specified' }}</b></span></div>
  </article>
  <article class="hm-public-feature-card"><div class="hm-public-feature-icon"><i class="las la-leaf"></i></div><h3>Lifestyle &amp; Interests</h3>
   <div class="hm-public-feature-facts"><span>Diet <b>{{ $user->lifestyles?->diet ?? 'Not specified' }}</b></span><span>Smoking <b>{{ $user->lifestyles?->smoke ?? 'Not specified' }}</b></span><span>Drinking <b>{{ $user->lifestyles?->drink ?? 'Not specified' }}</b></span><span>Hobbies <b>{{ is_array($member?->hobbies) ? implode(', ', $member->hobbies) : ($member?->hobbies ?: 'Not specified') }}</b></span></div>
  </article>
  @if(get_setting('member_partner_expectation_section') == 'on')
  <article class="hm-public-feature-card hm-public-feature-wide"><div class="hm-public-feature-icon"><i class="las la-ring"></i></div><h3>Partner Preferences</h3>
   @php $expectation = $user->partner_expectations; @endphp
   <div class="hm-public-feature-facts"><span>Preferred Age <b>{{ $expectation?->preferred_age_min && $expectation?->preferred_age_max ? $expectation->preferred_age_min.'–'.$expectation->preferred_age_max.' years' : 'Not specified' }}</b></span><span>Marital Status <b>{{ $expectation?->marital_status?->name ?? 'Not specified' }}</b></span><span>Education <b>{{ $expectation?->education ?: 'Not specified' }}</b></span><span>Profession <b>{{ $expectation?->profession ?: 'Not specified' }}</b></span></div>
   @if(filled($expectation?->general))<p>{{ $expectation->general }}</p>@endif
  </article>
  @endif
  <article class="hm-public-feature-card hm-public-feature-wide"><div class="hm-public-feature-icon"><i class="las la-images"></i></div><h3>Photo Gallery</h3>
   @if($galleryAllowed && $galleryImages->isNotEmpty())
    <div class="hm-public-gallery-grid">@foreach($galleryImages as $galleryPhoto)<img src="{{ uploaded_asset($galleryPhoto->image) }}" loading="lazy" alt="Member gallery photo">@endforeach</div>
   @elseif(!$galleryAllowed)<div class="hm-public-private"><i class="las la-lock"></i> Gallery is private. @unless($ownProfile)<button type="button" onclick="gallery_image_view_request({{ $user->id }})">Request access</button>@endunless</div>
   @else <div class="hm-public-private">No gallery photos uploaded yet.</div>@endif
  </article>
 </div>
</section>
</div>
