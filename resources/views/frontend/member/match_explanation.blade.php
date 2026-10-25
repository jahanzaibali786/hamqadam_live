@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
    $person = $match->matchedUser;
    $score = max(0, min(100, (int) round((float) $match->match_percentage)));
    $name = trim((string) ($person?->first_name ?? '') . ' ' . (string) ($person?->last_name ?? '')) ?: 'This member';
    $gender = (int) ($person?->member?->gender ?? 1);
    $fallback = static_asset($gender === 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
    $photo = $person?->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : $fallback;
    $reasons = array_values(array_filter((array) ($match->compatibility_reasons ?? [])));
    $concerns = array_values(array_filter((array) ($match->compatibility_concerns ?? [])));
    $breakdown = (array) ($match->score_breakdown ?? []);
    $recommended = array_values(array_filter((array) ($match->recommended_actions ?? [])));
    $level = $score >= 80 ? 'Strong compatibility' : ($score >= 60 ? 'Promising compatibility' : 'Potential compatibility');
    $canView = get_setting('full_profile_show_according_to_membership') != 1 ||
        (package_validity(auth()->id()) && (int) get_remaining_package_value(auth()->id(), 'remaining_profile_viewer_view') > 0) ||
        \App\Models\ProfileViewer::where('user_id', $person->id)->where('viewed_by', auth()->id())->exists();
    $detailUrl = $canView ? route('member_profile', $person->id) : route('member.reference.packages');
@endphp
<div class="hm-why-page">
    <header class="hm-why-top">
        <div class="hm-why-heading">
            <a class="hm-why-back" href="{{ route('my_matched_profiles') }}"><i class="las la-arrow-left"></i> Back to Matched Profiles</a>
            <span class="hm-why-eyebrow"><i class="las la-heart"></i> PERSONALIZED COMPATIBILITY</span>
            <h1>Why This Match?</h1>
            <p>Discover the shared qualities, meaningful values, and thoughtful factors behind your match.</p>
        </div>
        <div class="hm-why-mark"><i class="las la-heart"></i></div>
    </header>

    <div class="hm-why-overview">
        <section class="hm-why-person-card">
            <div class="hm-why-portrait">
                <img src="{{ $photo }}" alt="{{ $name }}'s profile photo" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallback }}'">
            </div>
            <div class="hm-why-person-meta">
                <span class="hm-why-verified"><i class="las la-shield-alt"></i> Member match</span>
                <h2>{{ $name }}</h2>
                <p>{{ $person?->addresses?->firstWhere('type', 'present')?->city?->name ?? 'Location not provided' }}</p>
                <a href="{{ $detailUrl }}" class="hm-why-outline"><i class="las la-user"></i> View Profile</a>
            </div>
        </section>

        <section class="hm-why-score-card" aria-label="{{ $score }} percent compatibility">
            <span class="hm-why-eyebrow">YOUR COMPATIBILITY SCORE</span>
            <div class="hm-why-score-ring" style="--hm-match-score:{{ $score }};">
                <div><strong>{{ $score }}%</strong><span>Match Score</span></div>
            </div>
            <h2>{{ $level }}</h2>
            <p>{{ $match->compatibility_explanation ?: 'Your compatibility is calculated from the preferences and profile details you have both provided.' }}</p>
            <div class="hm-why-score-note"><i class="las la-lock"></i> Private insights • Based on your saved preferences</div>
        </section>
    </div>

    <div class="hm-why-section-intro">
        <span class="hm-why-eyebrow">BEHIND THE COMPATIBILITY</span>
        <h2>What makes this connection meaningful?</h2>
        <p>These insights reflect the available profile information and saved matching analysis.</p>
    </div>

    <div class="hm-why-insights">
        <section class="hm-why-panel">
            <div class="hm-why-panel-heading"><span class="hm-why-panel-icon is-positive"><i class="las la-check-circle"></i></span><div><h3>Shared Strengths</h3><p>What aligns between your profiles</p></div></div>
            <div class="hm-why-reason-list">
                @forelse ($reasons as $reason)
                    <div class="hm-why-reason"><i class="las la-check-circle"></i><span>{{ is_scalar($reason) ? $reason : json_encode($reason, JSON_UNESCAPED_UNICODE) }}</span></div>
                @empty
                    <p class="hm-why-empty">Shared preference details are not available for this match yet.</p>
                @endforelse
            </div>
        </section>

        <section class="hm-why-panel">
            <div class="hm-why-panel-heading"><span class="hm-why-panel-icon"><i class="las la-lightbulb"></i></span><div><h3>Things to Consider</h3><p>Helpful points for a thoughtful introduction</p></div></div>
            <div class="hm-why-reason-list">
                @forelse ($concerns as $concern)
                    <div class="hm-why-reason is-consideration"><i class="las la-info-circle"></i><span>{{ is_scalar($concern) ? $concern : json_encode($concern, JSON_UNESCAPED_UNICODE) }}</span></div>
                @empty
                    <p class="hm-why-empty">No additional considerations have been recorded for this match.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if (count($breakdown))
        <section class="hm-why-panel hm-why-breakdown">
            <div class="hm-why-panel-heading"><span class="hm-why-panel-icon"><i class="las la-chart-bar"></i></span><div><h3>Compatibility Breakdown</h3><p>A closer look at the factors included in this match</p></div></div>
            <div class="hm-why-breakdown-grid">
                @foreach ($breakdown as $label => $value)
                    @php
                        $numeric = is_numeric($value) ? max(0, min(100, (float) $value)) : null;
                        $displayValue = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                    @endphp
                    <div class="hm-why-breakdown-item">
                        <div class="hm-why-breakdown-line"><strong>{{ ucwords(str_replace('_', ' ', (string) $label)) }}</strong><span>{{ $displayValue }}</span></div>
                        @if ($numeric !== null)
                            <div class="hm-why-progress"><span style="width:{{ $numeric }}%"></span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (count($recommended))
        <section class="hm-why-panel hm-why-guidance">
            <div class="hm-why-panel-heading"><span class="hm-why-panel-icon"><i class="las la-compass"></i></span><div><h3>Suggested Next Steps</h3><p>Respectful ways to get to know one another</p></div></div>
            @foreach ($recommended as $action)
                <div class="hm-why-reason is-consideration"><i class="las la-arrow-right"></i><span>{{ is_scalar($action) ? $action : json_encode($action, JSON_UNESCAPED_UNICODE) }}</span></div>
            @endforeach
        </section>
    @endif

    <footer class="hm-why-footer">
        <div><span class="hm-why-eyebrow">A THOUGHTFUL BEGINNING</span><h2>Meaningful connections begin with understanding.</h2><p>Explore this member's profile and take the next step with care.</p></div>
        <a class="hm-why-primary" href="{{ $detailUrl }}">Explore Profile <i class="las la-arrow-right"></i></a>
    </footer>
</div>
@endsection
