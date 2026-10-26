@extends('frontend.layouts.member_panel')
@section('panel_content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-why-match-20261026.css') }}?v=1">
@php
    $score = max(0, min(100, (int) round((float) $match->match_percentage)));
    $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: 'Member';
    $viewerName = trim(($viewer->first_name ?? '') . ' ' . ($viewer->last_name ?? '')) ?: 'You';
    $fallback = static_asset(($person->member?->gender ?? 1) == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
    $viewerFallback = static_asset(($viewer->member?->gender ?? 1) == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
    $photo = $person->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : $fallback;
    $viewerPhoto = $viewer->photo ? uploaded_asset($viewer->photo) : $viewerFallback;
    $canView = get_setting('full_profile_show_according_to_membership') != 1 ||
        (package_validity($viewer->id) && (int) get_remaining_package_value($viewer->id, 'remaining_profile_viewer_view') > 0) ||
        \App\Models\ProfileViewer::where('user_id', $person->id)->where('viewed_by', $viewer->id)->exists();
    $detailUrl = $canView ? route('member_profile', $person->id) : route('member.reference.packages');
    $candidateCity = $person->addresses->firstWhere('type', 'present')?->city?->name ?? 'Location not provided';
    $viewerCity = $viewer->addresses->firstWhere('type', 'present')?->city?->name ?? 'Location not provided';
    $age = fn ($member) => !empty($member?->birthday) ? \Carbon\Carbon::parse($member->birthday)->age . ' years' : 'Age not provided';
    $education = fn ($user) => $user->education->last()?->degree ?: ($user->member?->degree?->name ?? 'Education not provided');
    $profession = fn ($user) => $user->member?->job_title ?: ($user->career->last()?->designation ?: 'Profession not provided');
    $religion = fn ($user) => $user->spiritual_backgrounds?->religion?->name ?? 'Religion not provided';
    $summary = trim((string) $match->compatibility_explanation) ?: 'Your compatibility is based on the matching preferences and profile information currently available.';
    $status = $matchActions['status'];
    $thread = $matchActions['thread'];
    $interest = $matchActions['interest'];
    $isVerified = (bool) ($person->member?->verification_badge || $person->member?->trust_badge);
    $scoreLabel = $score >= 80 ? 'High Compatibility' : ($score >= 60 ? 'Good Compatibility' : 'Potential Compatibility');
    $insights = [
        ['icon' => 'la-comment-dots', 'title' => 'Communication Style', 'value' => ($viewer->member?->communication_style && $person->member?->communication_style && strcasecmp($viewer->member->communication_style, $person->member->communication_style) === 0) ? 'You both prefer a similar communication style.' : 'Learn how each other prefers to communicate.'],
        ['icon' => 'la-users', 'title' => 'Family Values', 'value' => count($sharedValues) ? 'Shared values: ' . implode(', ', array_slice($sharedValues, 0, 2)) : 'Compare your family values as you get to know each other.'],
        ['icon' => 'la-home', 'title' => 'Lifestyle Alignment', 'value' => ($viewer->lifestyles?->diet && $viewer->lifestyles?->diet === $person->lifestyles?->diet) ? 'You share similar dietary habits.' : 'Discuss day-to-day habits and lifestyle preferences.'],
        ['icon' => 'la-map-marker', 'title' => 'Location Compatibility', 'value' => $viewerCity !== 'Location not provided' && $viewerCity === $candidateCity ? 'You are both based in ' . $viewerCity . '.' : 'Your locations: ' . $viewerCity . ' and ' . $candidateCity . '.'],
    ];
    $tabs = [
        ['id' => 'overview', 'icon' => 'la-chart-bar', 'label' => 'Compatibility Overview'],
        ['id' => 'values', 'icon' => 'la-heart', 'label' => 'Shared Values'],
        ['id' => 'lifestyle', 'icon' => 'la-home', 'label' => 'Lifestyle Match'],
        ['id' => 'family', 'icon' => 'la-users', 'label' => 'Family & Background'],
        ['id' => 'career', 'icon' => 'la-graduation-cap', 'label' => 'Education & Career'],
        ['id' => 'analysis', 'icon' => 'la-file-alt', 'label' => 'Detailed Analysis'],
    ];
@endphp
<div class="hm-why26" id="hm-why26">
    @if($errors->has('proposal')) <div class="alert alert-danger" role="alert">{{ $errors->first('proposal') }}</div> @endif
    @if(session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
    <header class="hm-why26-hero">
        <div class="hm-why26-hero-title">
            <div class="hm-why26-spark"><i class="las la-magic" aria-hidden="true"></i></div>
            <div><h1>Why This Match?</h1><p>Discover how you and {{ $person->first_name }} are a great match.</p></div>
        </div>
        <div class="hm-why26-quote"><i class="las la-quote-left" aria-hidden="true"></i><span>Shared values create<br>stronger connections.</span><i class="las la-heart" aria-hidden="true"></i></div>
    </header>
    <section class="hm-why26-topgrid" aria-label="Match comparison">
        <div class="hm-why26-comparison">
            <article class="hm-why26-profile hm-why26-profile-left">
                <div class="hm-why26-profile-main">
                    <a class="hm-why26-portrait" href="{{ $detailUrl }}"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'"><span class="hm-why26-photo-badge">{{ $isVerified ? 'Verified' : 'Member' }}</span></a>
                    <div class="hm-why26-profile-copy">
                        <h2>{{ $name }} @if($isVerified)<i class="las la-check-circle hm-why26-blue-check" title="Verified"></i>@endif</h2>
                        <p class="hm-why26-sub">{{ $age($person->member) }} <span>·</span> {{ $candidateCity }}</p>
                        <p><i class="las la-briefcase"></i> {{ $profession($person) }}</p>
                        <p><i class="las la-moon"></i> {{ $religion($person) }}</p>
                        <p><i class="las la-graduation-cap"></i> {{ $education($person) }}</p>
                    </div>
                </div>
                <div class="hm-why26-thumbnails" aria-label="Member photographs"><span class="hm-why26-thumb"><img src="{{ $photo }}" alt="" loading="lazy"></span><span class="hm-why26-thumb"><img src="{{ $fallback }}" alt="" loading="lazy"></span><span class="hm-why26-thumb hm-why26-muted"><i class="las la-heart"></i></span><span class="hm-why26-thumb hm-why26-muted"><i class="las la-star"></i></span><a class="hm-why26-thumb hm-why26-more" href="{{ $detailUrl }}">View<br>profile</a></div>
            </article>
            <section class="hm-why26-score" aria-label="Compatibility score {{ $score }} percent">
                <div class="hm-why26-score-ring" style="--hm-why26-score:{{ $score }};"><div><i class="las la-heart" aria-hidden="true"></i><strong>{{ $score }}%</strong></div></div>
                <h2>{{ $scoreLabel }}</h2><p>{{ $score >= 80 ? 'Your profiles show strong alignment in matching preferences.' : 'Explore the factors that contribute to your compatibility.' }}</p>
                @if(!$interest)
                    <form action="{{ route('member.match.propose', $person->id) }}" method="POST">@csrf<button type="submit" class="hm-why26-button">Send Interest <i class="las la-arrow-right"></i></button></form>
                @elseif($status === 1)
                    <a class="hm-why26-button" href="{{ $thread ? route('chat_view', $thread->id) : route('all.messages') }}">Start Conversation <i class="las la-arrow-right"></i></a>
                @else
                    <span class="hm-why26-sent">Interest {{ $status === 0 ? 'Sent' : 'Closed' }}</span>
                @endif
            </section>
            <article class="hm-why26-profile hm-why26-profile-right">
                <div class="hm-why26-profile-main">
                    <div class="hm-why26-portrait"><img src="{{ $viewerPhoto }}" alt="{{ $viewerName }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $viewerFallback }}'"><span class="hm-why26-photo-badge">Your Profile</span></div>
                    <div class="hm-why26-profile-copy">
                        <h2>{{ $viewerName }}</h2>
                        <p class="hm-why26-sub">{{ $age($viewer->member) }} <span>·</span> {{ $viewerCity }}</p>
                        <p><i class="las la-briefcase"></i> {{ $profession($viewer) }}</p>
                        <p><i class="las la-moon"></i> {{ $religion($viewer) }}</p>
                        <p><i class="las la-graduation-cap"></i> {{ $education($viewer) }}</p>
                    </div>
                </div>
                <div class="hm-why26-thumbnails"><span class="hm-why26-thumb"><img src="{{ $viewerPhoto }}" alt="" loading="lazy"></span><span class="hm-why26-thumb"><img src="{{ $viewerFallback }}" alt="" loading="lazy"></span><span class="hm-why26-thumb hm-why26-muted"><i class="las la-heart"></i></span><span class="hm-why26-thumb hm-why26-muted"><i class="las la-star"></i></span><a class="hm-why26-thumb hm-why26-more" href="{{ route('profile_settings') }}">Edit<br>profile</a></div>
            </article>
        </div>
        <aside class="hm-why26-actions"><h2>Quick Actions</h2>
            @if(!$interest)<form method="POST" action="{{ route('member.match.propose', $person->id) }}">@csrf<button type="submit"><i class="las la-heart"></i> Send Interest</button></form>
            @else <span class="hm-why26-action-disabled"><i class="las la-heart"></i> {{ $status === 1 ? 'Interest Accepted' : 'Interest Already Sent' }}</span>@endif
            @if($status === 1)<a href="{{ $thread ? route('chat_view', $thread->id) : route('all.messages') }}"><i class="las la-comment-dots"></i> Start Conversation</a>
            @else <span class="hm-why26-action-disabled" title="Messaging becomes available after interest acceptance"><i class="las la-comment-dots"></i> Chat after acceptance</span>@endif
            @if($isShortlisted)<span class="hm-why26-action-disabled"><i class="las la-bookmark"></i> Already Shortlisted</span>
            @elseif($status === 1)<button type="button" data-why-action="shortlist"><i class="las la-bookmark"></i> Add to Shortlist</button>
            @else <span class="hm-why26-action-disabled" title="Shortlisting requires accepted interest"><i class="las la-bookmark"></i> Shortlist after acceptance</span>@endif
            <a href="{{ route('member.reference.packages') }}"><i class="las la-rocket"></i> Premium Features</a>
            <button type="button" data-why-action="share"><i class="las la-share-alt"></i> Share Profile</button>
            <form action="{{ route('reportusers.store') }}" method="POST" class="hm-why26-report">@csrf<input type="hidden" name="member_id" value="{{ $person->id }}"><input type="hidden" name="reason" value=""><button type="submit"><i class="las la-flag"></i> Report Profile</button></form>
            <button type="button" data-why-action="block"><i class="las la-ban"></i> Block User</button>
            <span class="hm-why26-feedback" id="hm-why26-feedback" role="status" aria-live="polite"></span>
        </aside>
    </section>
    <nav class="hm-why26-tabs" role="tablist" aria-label="Compatibility details">
        @foreach($tabs as $i => $tab)<button type="button" id="hm-why26-tab-{{ $tab['id'] }}" class="{{ $i === 0 ? 'is-active' : '' }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="hm-why26-panel-{{ $tab['id'] }}" data-why-tab="{{ $tab['id'] }}"><i class="las {{ $tab['icon'] }}"></i> {{ $tab['label'] }}</button>@endforeach
    </nav>
    <div class="hm-why26-panels">
        <section class="hm-why26-panel is-active" id="hm-why26-panel-overview" role="tabpanel" aria-labelledby="hm-why26-tab-overview">
            <div class="hm-why26-analysis-grid">
                <section class="hm-why26-card hm-why26-factors"><h2>Key Matching Factors</h2>
                    @foreach($factorScores as $factor)<div class="hm-why26-factor"><i class="las {{ $factor['icon'] }}"></i><span>{{ $factor['label'] }}</span><div class="hm-why26-track" role="meter" aria-label="{{ $factor['label'] }}" aria-valuemin="0" aria-valuemax="100" @if($factor['score'] !== null) aria-valuenow="{{ $factor['score'] }}" @else aria-valuetext="Not available" @endif><span style="width:{{ $factor['score'] ?? 0 }}%"></span></div><b>{{ $factor['score'] !== null ? $factor['score'].'%' : '—' }}</b></div>@endforeach
                </section>
                <section class="hm-why26-card hm-why26-summary"><div class="hm-why26-card-heading"><h2>AI Match Summary</h2>@if($match->ai_enhanced)<span>AI</span>@endif</div>
                    <p class="hm-why26-summary-text"><i class="las la-magic"></i>{{ $summary }}</p>
                    <h3>Top Reasons for This Match</h3>
                    @forelse(array_slice($reasons,0,5) as $i => $reason)<p class="hm-why26-reason"><b>{{ $i+1 }}</b>{{ $reason }}</p>@empty<p class="hm-why26-empty">Detailed reasons are not available yet.</p>@endforelse
                </section>
                <section class="hm-why26-card hm-why26-additional"><h2>Additional Insights</h2>@foreach($insights as $insight)<div class="hm-why26-insight"><i class="las {{ $insight['icon'] }}"></i><div><h3>{{ $insight['title'] }}</h3><p>{{ $insight['value'] }}</p></div></div>@endforeach</section>
            </div>
            <div class="hm-why26-bottomrow">
                <section class="hm-why26-card hm-why26-interests"><div class="hm-why26-card-heading"><h2>Shared Interests</h2><a href="{{ $detailUrl }}">View Profile <i class="las la-arrow-right"></i></a></div><div class="hm-why26-chips">@forelse($sharedInterests as $interest)<span><i class="las la-heart"></i> {{ $interest }}</span>@empty<p class="hm-why26-empty">Shared interests have not been recorded in both profiles.</p>@endforelse</div></section>
                <aside class="hm-why26-upgrade"><i class="las la-crown"></i><div><h3>Get Higher Compatibility</h3><p>Explore premium features and deeper match insights.</p><a href="{{ route('member.reference.packages') }}">View Premium Plans <i class="las la-arrow-right"></i></a></div></aside>
            </div>
        </section>
        <section class="hm-why26-panel" id="hm-why26-panel-values" role="tabpanel" aria-labelledby="hm-why26-tab-values" hidden><div class="hm-why26-card hm-why26-detail"><h2><i class="las la-heart"></i> Shared Values</h2><p>Values identified in both saved profiles.</p><div class="hm-why26-chips">@forelse($sharedValues as $value)<span>{{ $value }}</span>@empty<p class="hm-why26-empty">No shared values have been explicitly recorded yet.</p>@endforelse</div></div></section>
        <section class="hm-why26-panel" id="hm-why26-panel-lifestyle" role="tabpanel" aria-labelledby="hm-why26-tab-lifestyle" hidden><div class="hm-why26-card hm-why26-detail"><h2><i class="las la-home"></i> Lifestyle Match</h2><div class="hm-why26-detail-grid"><div><h3>Your dietary preference</h3><p>{{ $viewer->lifestyles?->diet ?: 'Not provided' }}</p></div><div><h3>{{ $person->first_name }}'s dietary preference</h3><p>{{ $person->lifestyles?->diet ?: 'Not provided' }}</p></div><div><h3>Your communication style</h3><p>{{ $viewer->member?->communication_style ?: 'Not provided' }}</p></div><div><h3>{{ $person->first_name }}'s communication style</h3><p>{{ $person->member?->communication_style ?: 'Not provided' }}</p></div></div></div></section>
        <section class="hm-why26-panel" id="hm-why26-panel-family" role="tabpanel" aria-labelledby="hm-why26-tab-family" hidden><div class="hm-why26-card hm-why26-detail"><h2><i class="las la-users"></i> Family & Background</h2><div class="hm-why26-detail-grid"><div><h3>Your family values</h3><p>{{ $viewer->member?->family_values ?: 'Not provided' }}</p></div><div><h3>{{ $person->first_name }}'s family values</h3><p>{{ $person->member?->family_values ?: 'Not provided' }}</p></div><div><h3>Your family type</h3><p>{{ $viewer->member?->family_type ?: 'Not provided' }}</p></div><div><h3>{{ $person->first_name }}'s family type</h3><p>{{ $person->member?->family_type ?: 'Not provided' }}</p></div></div></div></section>
        <section class="hm-why26-panel" id="hm-why26-panel-career" role="tabpanel" aria-labelledby="hm-why26-tab-career" hidden><div class="hm-why26-card hm-why26-detail"><h2><i class="las la-graduation-cap"></i> Education & Career</h2><div class="hm-why26-detail-grid"><div><h3>Your Education</h3><p>{{ $education($viewer) }}</p></div><div><h3>{{ $person->first_name }}'s Education</h3><p>{{ $education($person) }}</p></div><div><h3>Your Profession</h3><p>{{ $profession($viewer) }}</p></div><div><h3>{{ $person->first_name }}'s Profession</h3><p>{{ $profession($person) }}</p></div></div></div></section>
        <section class="hm-why26-panel" id="hm-why26-panel-analysis" role="tabpanel" aria-labelledby="hm-why26-tab-analysis" hidden><div class="hm-why26-card hm-why26-detail"><h2><i class="las la-file-alt"></i> Detailed Match Analysis</h2><p>{{ $summary }}</p><h3>Matching Strengths</h3>@forelse($reasons as $reason)<p class="hm-why26-listline"><i class="las la-check-circle"></i> {{ $reason }}</p>@empty<p class="hm-why26-empty">No additional strengths recorded.</p>@endforelse<h3>Things to Consider</h3>@forelse($concerns as $concern)<p class="hm-why26-listline"><i class="las la-info-circle"></i> {{ $concern }}</p>@empty<p class="hm-why26-empty">No considerations recorded.</p>@endforelse<h3>Suggested Next Steps</h3>@forelse($recommended as $action)<p class="hm-why26-listline"><i class="las la-arrow-right"></i> {{ $action }}</p>@empty<p class="hm-why26-empty">Explore the profile and connect when you are ready.</p>@endforelse</div></section>
    </div>
    <form id="hm-why26-block-form" method="POST" action="{{ route('member.add_to_ignore_list') }}" hidden>@csrf<input name="id" value="{{ $person->id }}"></form>
    <form id="hm-why26-shortlist-form" method="POST" action="{{ route('member.add_to_shortlist') }}" hidden>@csrf<input name="id" value="{{ $person->id }}"></form>
</div>
@endsection
@section('script')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const root=document.getElementById('hm-why26');if(!root)return;
    const tabs=[...root.querySelectorAll('[data-why-tab]')];
    function selectTab(tab,focus){
        tabs.forEach(button=>{const selected=button.dataset.whyTab===tab;button.classList.toggle('is-active',selected);button.setAttribute('aria-selected',selected?'true':'false');button.tabIndex=selected?0:-1;if(selected&&focus)button.focus();});
        root.querySelectorAll('.hm-why26-panel').forEach(panel=>{const selected=panel.id==='hm-why26-panel-'+tab;panel.hidden=!selected;panel.classList.toggle('is-active',selected);});
    }
    tabs.forEach((button,index)=>{button.addEventListener('click',()=>selectTab(button.dataset.whyTab,false));button.addEventListener('keydown',event=>{let next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;else if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();selectTab(tabs[next].dataset.whyTab,true);});});
    const feedback=root.querySelector('#hm-why26-feedback');
    const report=root.querySelector('.hm-why26-report');
    report?.addEventListener('submit',event=>{const reason=window.prompt('Please provide a reason for reporting this profile.');if(!reason||!reason.trim()){event.preventDefault();return;}report.querySelector('[name=reason]').value=reason.trim();});
    function sendAction(formId,confirmText,successText){
        if(confirmText&&!window.confirm(confirmText))return;
        const form=root.querySelector('#'+formId);const data=new FormData(form);
        fetch(form.action,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
          .then(res=>{if(!res.ok)throw new Error('Request failed');return res.text();})
          .then(body=>{if(body.trim()!=='1')throw new Error('Action unavailable or insufficient coins');feedback.textContent=successText;const button=root.querySelector('[data-why-action="'+(formId.includes('block')?'block':'shortlist')+'"]');if(button)button.disabled=true;if(formId.includes('block'))window.location.href=@json(route('my_matched_profiles'));})
          .catch(()=>{feedback.textContent='Unable to complete this action. Please check your account or package.';});
    }
    root.querySelector('[data-why-action=shortlist]')?.addEventListener('click',()=>sendAction('hm-why26-shortlist-form','Shortlisting may use coins. Continue?','Profile added to shortlist.'));
    root.querySelector('[data-why-action=block]')?.addEventListener('click',()=>sendAction('hm-why26-block-form','Block this member? The match will be hidden.','Member blocked.'));
    root.querySelector('[data-why-action=share]')?.addEventListener('click',async()=>{const url=@json(route('member_profile',$person->id));try{if(navigator.share){await navigator.share({title:@json($name),url});}else if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(url);feedback.textContent='Profile link copied.';}else{window.prompt('Copy this profile link:',url);}}catch(error){if(error.name!=='AbortError')feedback.textContent='Unable to share profile.';}});
});
</script>
@endsection
