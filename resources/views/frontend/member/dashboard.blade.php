@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
 $me=auth()->user();$member=$me->member;$pct=max(0,min(100,(int)($member?->profile_completion_percentage??0)));
 $top=$dashboardTopMatch;$best=$top?->matchedUser;$bestScore=$top?max(0,min(100,round($top->match_percentage))):0;
 $avatar=$me->photo?uploaded_asset($me->photo):static_asset('assets/img/avatar-place.png');
 $verified=app(\App\Services\BadgeService::class)->isVerified($member);
 $myAddress=$me->addresses?->firstWhere('type','present');
@endphp
<div class="hm-ref26 hm-dash26">
 <header class="hm-dash26-welcome"><div><h1>Welcome back, <span>{{ $me->first_name }}!</span> 👋</h1><p>Let's find meaningful connections for your future.</p></div><div class="hm-dash26-quote"><i class="las la-quote-left"></i><strong>Every person has a story.<br>Find yours.</strong><img src="{{ static_asset('assets/img/member-reference-couple.svg') }}" alt=""></div></header>
 <div class="hm-dash26-upper">
  <div class="hm-dash26-left">
   <div class="hm-dash26-stats">
    <a href="{{ route('member.verification') }}"><i class="las la-check-circle green"></i><span>Profile Status<strong>{{ $verified?'Verified':'Pending' }}</strong><small>Identity verification</small></span></a>
    <a href="{{ route('wallet.index') }}"><i class="las la-coins gold"></i><span>Coin Balance<strong>{{ number_format((int)($member?->remaining_interest??0)) }}</strong><small>View Usage</small></span></a>
    <a href="{{ route('member.reference.packages') }}"><i class="las la-crown pink"></i><span>Current Package<strong>{{ $member?->package?->name??'Free' }}</strong><small>Upgrade Now</small></span></a>
    <a href="{{ route('profile-viewers.index') }}"><i class="las la-eye purple"></i><span>Profile Views<strong>{{ number_format($dashboardViewerCount) }}</strong><small>This month</small></span></a>
    <a href="{{ route('my_matched_profiles') }}"><i class="las la-heart pink"></i><span>Matches<strong>{{ number_format($dashboardMatchCount) }}</strong><small>AI suggested</small></span></a>
   </div>
   <section class="hm-ref26-card hm-dash26-recommend"><div class="hm-ref26-section-head"><h2>Recommended Matches For You</h2><a href="{{ route('my_matched_profiles') }}">View All</a></div><div class="hm-dash26-profiles">
    @forelse($dashboardMatches as $match)
    @php
     $person=$match->matchedUser;$related=$person->member;$fallback=static_asset(($related?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png');
     $photo=$person->photo&&show_profile_picture($person)?uploaded_asset($person->photo):$fallback;
     $loc=$person->addresses->firstWhere('type','present');
     $already=\App\Models\ProfileViewer::where('user_id',$person->id)->where('viewed_by',$me->id)->exists();
     $canView=get_setting('full_profile_show_according_to_membership')!=1||$already||(package_validity($me->id)&&(int)get_remaining_package_value($me->id,'remaining_profile_viewer_view')>0);
    @endphp
    <article class="hm-dash26-person"><a href="{{ $canView?route('member_profile',$person->id):route('packages') }}" class="hm-dash26-photo"><img src="{{ $photo }}" onerror="this.onerror=null;this.src='{{ $fallback }}'" loading="lazy" alt="{{ $person->first_name }} profile">
     <span class="hm-dash26-heart"><i class="las la-heart"></i></span></a>
     <strong>{{ $person->first_name }}@if($related?->birthday), {{ \Carbon\Carbon::parse($related->birthday)->age }}@endif</strong>
     <small>{{ $loc?->city?->name??'Location not provided' }}</small><p><i class="las la-briefcase"></i> {{ $related?->job_title??'Profession not provided' }}</p><div><b><i class="las la-heart"></i> {{ round($match->match_percentage) }}% Match</b><a href="{{ $canView?route('member_profile',$person->id):route('packages') }}" aria-label="View {{ $person->first_name }} profile"><i class="las la-ellipsis-v"></i></a></div>
    </article>
    @empty <p class="hm-ref26-empty">No compatible profiles are available yet. <a href="{{ route('profile_settings') }}">Update your preferences</a>.</p>@endforelse
   </div></section>
  </div>
  <aside class="hm-ref26-card hm-dash26-profile"><img class="hm-dash26-avatar" src="{{ $avatar }}" alt="My profile"><h2>{{ $me->first_name }} {{ $me->last_name }} @if($verified)<span>✓ Verified</span>@endif</h2><p>{{ $member?->birthday?\Carbon\Carbon::parse($member->birthday)->age.' years · ':'' }}{{ $myAddress?->city?->name??'Location not provided' }}</p><p><i class="las la-briefcase"></i> {{ $member?->job_title??'Profession not provided' }}</p><a class="hm-ref26-primary" href="{{ route('profile_settings') }}"><i class="las la-edit"></i> Edit Profile</a><div class="hm-dash26-completion"><b>Profile Completion <span>{{ $pct }}%</span></b><div class="hm-ref26-track"><i style="width:{{ $pct }}%"></i></div><div class="hm-dash26-checks">@foreach([['Basic Information',filled($member?->birthday)],['Photos',filled($me->photo)],['Family Details',filled($member?->family_bio)],['Lifestyle Preferences',filled($member?->hobbies)],['Education & Career',filled($member?->job_title)],['Verification',$verified]] as $row)<span><i class="las {{ $row[1]?'la-check-circle':'la-circle' }}"></i>{{ $row[0] }}</span>@endforeach</div></div></aside>
 </div>
 <div class="hm-dash26-bottom">
  <section class="hm-ref26-card"><div class="hm-ref26-section-head"><h2>Recent Activity</h2><a href="{{ route('frontend.notifications') }}">View All <i class="las la-angle-right"></i></a></div>@forelse($dashboardActivity as $notification)<div class="hm-dash26-activity"><i class="las la-bell"></i><div><strong>{{ \Illuminate\Support\Str::limit((string)data_get($notification->data,'message',data_get($notification->data,'title','Account update')),85) }}</strong><small>{{ $notification->created_at?->diffForHumans() }}</small></div></div>@empty<p class="hm-ref26-empty">No recent account activity.</p>@endforelse</section>
  <section class="hm-ref26-card"><div class="hm-ref26-section-head"><h2>Your Compatibility Insights</h2><a href="{{ route('client.scope.portal') }}">See Details</a></div>@if($top)<div class="hm-dash26-compat"><div><div class="hm-dash26-ring" style="--progress:{{ $bestScore }}"><strong>{{ $bestScore }}%</strong></div><b>{{ $bestScore>=80?'High':'Potential' }} Compatibility</b><p>{{ \Illuminate\Support\Str::limit((string)($top->compatibility_explanation?:'Matching based on your preferences'),95) }}</p></div><ul>@forelse(array_slice((array)($top->compatibility_reasons??[]),0,5) as $reason)<li><i class="las la-check"></i> {{ is_string($reason)?\Illuminate\Support\Str::limit($reason,55):'Shared preference' }}</li>@empty<li>Review your shared preferences.</li>@endforelse</ul></div><a class="hm-ref26-primary" href="{{ route('client.scope.portal') }}">View Full Compatibility</a>@else<p class="hm-ref26-empty">Complete your preferences to view compatibility.</p>@endif</section>
  <section class="hm-ref26-card"><div class="hm-ref26-section-head"><h2>Quick Actions</h2></div><div class="hm-dash26-actions">@foreach([['Search Profiles','member.listing','la-search'],['Send Interest','my_matched_profiles','la-heart'],['Messages','all.messages','la-comment-dots'],['Shortlist','my_shortlists','la-bookmark'],['Profile Boost','member.reference.packages','la-rocket'],['Buy Coins','custom_coins.form','la-coins'],['My Packages','member.reference.packages','la-crown'],['Earn Coins','member.referrals.index','la-gift'],['Help & Support','client.scope.support','la-headset']] as $a)<a href="{{ route($a[1]) }}"><i class="las {{ $a[2] }}"></i><span>{{ $a[0] }}</span></a>@endforeach</div></section>
  <section class="hm-ref26-card"><div class="hm-ref26-section-head"><h2>Happy Stories</h2><a href="{{ route('member.reference.stories') }}">View All</a></div>@forelse($dashboardStories as $story)@php($photos=array_values(array_filter(explode(',',(string)$story->photos))))<a class="hm-dash26-story" href="{{ route('story_details',\App\Support\StoryPublicId::encode((int)$story->id)) }}"><img src="{{ count($photos)?uploaded_asset($photos[0]):static_asset('assets/img/placeholder.jpg') }}" alt=""><div><p>{{ \Illuminate\Support\Str::limit(strip_tags((string)$story->details),135) }}</p><b>{{ $story->title }}</b></div></a>@empty<p class="hm-ref26-empty">No approved stories yet.</p>@endforelse</section>
  <section class="hm-ref26-card"><div class="hm-ref26-section-head"><h2>Latest Blogs &amp; Articles</h2><a href="{{ route('blog') }}">View All</a></div><div class="hm-dash26-blogs">@forelse($dashboardBlogs as $blog)<a href="{{ route('blog_details',$blog->slug) }}"><img src="{{ $blog->banner?uploaded_asset($blog->banner):static_asset('assets/img/default-blog.png') }}" alt=""><b>{{ $blog->title }}</b></a>@empty<p class="hm-ref26-empty">No published articles yet.</p>@endforelse</div></section>
  <section class="hm-ref26-card hm-dash26-upgrade"><i class="las la-crown"></i><div><h2>Upgrade to Premium</h2><p>Unlock more matches, advanced filters and premium features.</p><a class="hm-ref26-primary" href="{{ route('member.reference.packages') }}">View Plans <i class="las la-arrow-right"></i></a></div></section>
 </div>
</div>
@endsection