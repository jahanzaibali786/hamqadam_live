@extends('frontend.layouts.member_panel')

@section('panel_content')
<div class="hm-four hm-matched-card-page">
    @if ($errors->has('proposal'))
        <div class="alert alert-danger">{{ $errors->first('proposal') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <header class="hm-four-head">
        <div>
            <h1><i class="las la-user-friends"></i> Matched Profiles</h1>
            <p>People who are a great match based on your profile and preferences.</p>
        </div>
        <aside>Better matches lead to brighter futures.</aside>
    </header>

    <div class="hm-four-layout">
        <main>
            <nav class="hm-four-tabs" aria-label="Match filters">
                <a class="{{ !request('tab') || request('tab') === 'all' ? 'active' : '' }}" href="{{ route('my_matched_profiles') }}">All Matches ({{ $matchedProfiles->total() }})</a>
                <a class="{{ request('tab') === 'high' ? 'active' : '' }}" href="{{ route('my_matched_profiles', ['tab' => 'high']) }}">High Compatibility</a>
                <a class="{{ request('tab') === 'new' ? 'active' : '' }}" href="{{ route('my_matched_profiles', ['tab' => 'new']) }}">New Matches</a>
                <a class="{{ request('tab') === 'recent' ? 'active' : '' }}" href="{{ route('my_matched_profiles', ['tab' => 'recent']) }}">Recently Active</a>
            </nav>

            <div class="hm-four-matches hm-matched-card-grid">
                @forelse ($matchedProfiles as $match)
                    @php
                        $person = $match->matchedUser;
                    @endphp

                    @if ($person)
                        @php
                            $member = $person->member;
                            $fallback = static_asset(($member?->gender ?? 1) == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png');
                            $photo = $person->photo && show_profile_picture($person) ? uploaded_asset($person->photo) : $fallback;
                            // Member profile details require login, not an upgrade.
                            // Keep the same View Profile destination as the dashboard.
                            $detailUrl = route('member_profile', $person->id);
                            $matchActions = \App\Support\MemberMatchCardActions::between(auth()->user(), $person);
                        @endphp

                        @include('frontend.member.partials.compact_match_card', ['cardContext' => 'matched', 'person' => $person, 'match' => $match, 'photo' => $photo, 'fallback' => $fallback, 'detailUrl' => $detailUrl])
                    @endif
                @empty
                    <p class="hm-four-empty">No compatible profiles match these filters.</p>
                @endforelse
            </div>

            {{ $matchedProfiles->links() }}
        </main>

        <aside>
            <section class="hm-four-panel">
                <h2>Match Insights</h2>
                <div class="hm-four-insight">♥ <strong>{{ $matchedProfiles->total() }}</strong><small>Matching profiles in current filter</small></div>
                <a href="{{ route('client.scope.portal') }}">View Compatibility Analysis →</a>
            </section>
            <section class="hm-four-panel">
                <h2>Filters</h2>
                <form action="{{ route('my_matched_profiles') }}" method="GET">
                    <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
                    <label>Search Matches<input name="search" placeholder="Name" value="{{ request('search') }}"></label>
                    <label>Minimum Age<input type="number" name="min_age" min="18" max="90" value="{{ request('min_age', 20) }}"></label>
                    <label>Maximum Age<input type="number" name="max_age" min="18" max="90" value="{{ request('max_age', 35) }}"></label>
                    <label class="hm-four-check"><input type="checkbox" name="verified" value="1" @checked(request('verified'))> Verified Profiles Only</label>
                    <button type="submit">Apply Filters</button>
                </form>
            </section>
            <section class="hm-four-promo"><a href="{{ route('member.reference.packages') }}">Upgrade to Premium →</a></section>
        </aside>
    </div>
</div>
@endsection
