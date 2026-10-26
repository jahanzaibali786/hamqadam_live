@php
    $listingRoute = request()->routeIs('members.public_listing')
        ? 'members.public_listing'
        : 'member.listing';
@endphp

<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-four-pages-20261024.css') }}?v=1">
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-discovery-contexts-20261024.css') }}?v=4">
<div class="hq-ref24 hq-ref24-discovery {{ request()->routeIs('members.public_listing') ? 'hq24-standalone' : 'hq24-dashboard' }}">
    <section class="hq-reference-page-hero hq-discovery-reference-hero">
        <div class="container">
            <nav class="hq24-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">{{ translate('Home') }}</a> / {{ translate('Active Members Discovery') }}</nav>
            <!-- <div class="hq-reference-breadcrumb">{{ translate('Home') }} <span>/</span> {{ translate('Active Members Discovery') }}</div> -->
            <span class="hq-reference-eyebrow"><i class="las la-certificate"></i> {{ translate('Curated Sanctuary of Verified Hearts') }}</span>
            <h1>{{ translate('Active Members Discovery') }}</h1>
            <p>{{ translate('Explore prospective matches with family-minded discovery, verified identities, and private communication.') }}</p>
            <div class="hq-discovery-meta">
                <span><i class="las la-circle"></i> <strong>{{ number_format($users->total()) }}</strong> {{ translate('Active Profiles') }}</span>
                <span><i class="las la-shield-alt"></i> {{ translate('Verified Community') }}</span>
                <span><i class="las la-comments"></i> {{ translate('Protected Communication') }}</span>
            </div>
        </div>
    </section>
    <section class="hq-reference-discovery-page py-4 py-lg-5">
        <div class="container">
            <div class="hq24-filter-row"><span class="hq24-filter-label">{{ translate('Filters') }}:</span>
                @foreach (['gender' => 'Gender', 'age_from' => 'Min age', 'age_to' => 'Max age', 'city_id' => 'City', 'profession' => 'Profession', 'religion_id' => 'Religion', 'member_type' => 'Member type'] as $key => $label)
                    @if (request()->filled($key))
                        <span class="hq24-filter-chip">
                            {{ $label }}: {{ request($key) }}
                            <a href="{{ route($listingRoute, request()->except($key, 'page')) }}" aria-label="Remove {{ $label }} filter">&times;</a>
                        </span>
                    @endif
                @endforeach
                @if (count(request()->except('page', 'sort')))
                    <a class="hq24-clear" href="{{ route($listingRoute) }}">{{ translate('Reset All') }}</a>
                @endif
                <form class="hq24-sort" action="{{ route($listingRoute) }}" method="GET">
                    @foreach (request()->except('sort', 'page') as $k => $v)
                        @if (is_scalar($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                  <label for="hq24-sort-select">{{ translate('Sort') }}:</label>
                  <select name="sort" id="hq24-sort-select" onchange="this.form.submit()"><option value="match" @selected(request('sort','match')==='match')>{{ translate('Match % (High to Low)') }}</option><option value="recent" @selected(request('sort')==='recent')>{{ translate('Recently Active') }}</option><option value="newest" @selected(request('sort')==='newest')>{{ translate('Newest First') }}</option></select>
                </form>
                <div class="hq24-view-toggle" role="group" aria-label="Change member grid"><button type="button" class="is-selected" data-cols="two" title="Two columns" aria-label="Two columns" aria-pressed="true"><i class="las la-th-large"></i></button><button type="button" data-cols="one" title="One column" aria-label="One column" aria-pressed="false"><i class="las la-list"></i></button></div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-xl-3">
                            @include('frontend.member.member_listing.advanced_search')
                        </div>
                        <div class="col-xl-9">
                            <div class="d-flex">
                                <p class="hq24-results-count">{{ translate('Showing') }} <strong>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong> {{ translate('of') }} <strong>{{ number_format($users->total()) }}</strong> {{ translate('matches matching your family criteria') }}</p>
                                <div class="d-xl-none ml-auto mb-1 ml-xl-3 mr-0 align-self-end">
                                    <button type="button" class="btn btn-icon p-0 round-btn" data-toggle="class-toggle"
                                        data-target=".aiz-filter-sidebar">
                                        <i class="la la-list la-2x"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="hq-discovery-grid">
                                {{-- Render each member card with a standard Blade loop. --}}
                                @foreach ($users as $key => $user)
                                    @php
                                        $compatibility = $user->profile_match_for_viewer;
                                        $member = $user->member;
                                        $profile_picture_show = show_profile_picture($user);
                                        $photoUrl = $profile_picture_show ? uploaded_asset($user->photo) : null;
                                        $photoPath = strtolower((string) (parse_url((string) ($photoUrl ?? ''), PHP_URL_PATH) ?: ''));
                                        // Missing uploads can fall back to generic artwork; do not enlarge it as a profile photo.
                                        $fallbackImages = ['placeholder.jpg', 'avatar-place.png', 'female-avatar-place.png'];
                                        $photoFilename = basename($photoPath);
                                        $hasVisiblePhoto = $profile_picture_show
                                            && !empty($photoUrl)
                                            && !in_array($photoFilename, $fallbackImages, true);
                                        $photoFallbackLabel = $profile_picture_show
                                            ? translate('Photo unavailable')
                                            : translate('Photo private');
                                        $isVerified = app(\App\Services\BadgeService::class)->isVerified($member);
                                        $isOnline = Cache::has('user-is-online-' . $user->id);
                                        $age = !empty($member?->birthday) ? \Carbon\Carbon::parse($member->birthday)->age : null;
                                        $height = $user->physical_attributes?->height;
                                        $presentAddress = $user->addresses->where('type', 'present')->first();
                                        $locationParts = array_values(array_filter([
                                            $presentAddress?->city?->name ?? null,
                                            $presentAddress?->state?->name ?? null,
                                            $presentAddress?->country?->name ?? null,
                                        ]));
                                        $locationText = implode(', ', array_slice($locationParts, 0, 2));
                                        $career = $user->career->last();
                                        $education = $user->education->where('is_highest_degree', 1)->first() ?? $user->education->last();
                                        $religionName = $user->spiritual_backgrounds?->religion?->name;
                                        $casteName = $user->spiritual_backgrounds?->caste?->name;
                                        $maritalStatus = $member?->marital_status?->name;
                                        $languageName = null;
                                        if (!empty($member?->mothere_tongue)) {
                                            $languageName = \App\Models\MemberLanguage::where('id', $member->mothere_tongue)->value('name');
                                        }

                                        $remaining_full_profile_views = (int) get_remaining_package_value(Auth::user()->id, 'remaining_profile_viewer_view');
                                        $can_view_full_profile = package_validity(Auth::user()->id) && $remaining_full_profile_views > 0;
                                        $already_viewed_full_profile = \App\Models\ProfileViewer::where('user_id', $user->id)->where('viewed_by', Auth::user()->id)->exists();
                                        $profileNeedsUpgrade = get_setting('full_profile_show_according_to_membership') == 1
                                            && !$already_viewed_full_profile
                                            && !$can_view_full_profile;

                                        $interest_class = 'text-primary';
                                        $do_expressed_interest = \App\Models\ExpressInterest::where('user_id', $user->id)
                                            ->where('interested_by', Auth::user()->id)
                                            ->first();
                                        $received_expressed_interest = \App\Models\ExpressInterest::where('user_id', Auth::user()->id)
                                            ->where('interested_by', $user->id)
                                            ->first();
                                        if (!empty($do_expressed_interest)) {
                                            $interest_onclick = 0;
                                            $interest_text = $do_expressed_interest->status->value == 0 ? translate('Interest Sent') : translate('Interest Accepted');
                                        } elseif (!empty($received_expressed_interest)) {
                                            $interest_onclick = 'do_response';
                                            $interest_text = $received_expressed_interest->status->value == 0 ? translate('Respond to Interest') : translate('Interest Accepted');
                                        } else {
                                            $interest_onclick = 1;
                                            $interest_text = translate('Send Interest');
                                        }

                                        $shortlist = \App\Models\Shortlist::where('user_id', $user->id)
                                            ->where('shortlisted_by', Auth::user()->id)
                                            ->first();
                                        $can_shortlist = (!empty($do_expressed_interest) && $do_expressed_interest->status->value == 1)
                                            || (!empty($received_expressed_interest) && $received_expressed_interest->status->value == 1);
                                        if (empty($shortlist) && $can_shortlist) {
                                            $shortlist_onclick = 1;
                                            $shortlist_text = translate('Shortlist');
                                            $shortlist_prompt = '';
                                        } elseif (!empty($shortlist)) {
                                            $shortlist_onclick = 0;
                                            $shortlist_text = translate('Shortlisted');
                                            $shortlist_prompt = '';
                                        } else {
                                            $shortlist_onclick = -1;
                                            $shortlist_text = translate('Shortlist');
                                            $shortlist_prompt = empty($do_expressed_interest) && empty($received_expressed_interest)
                                                ? translate('Please express interest first before shortlisting this member.')
                                                : translate('Please wait for interest approval before shortlisting this member.');
                                        }

                                        $profile_reported = \App\Models\ReportedUser::where('user_id', $user->id)
                                            ->where('reported_by', Auth::user()->id)
                                            ->exists();
                                    @endphp

                                    <article class="hq-discovery-card" id="block_id_{{ $user->id }}">
                                        <div class="hq24-photo-frame {{ $hasVisiblePhoto ? 'hq24-has-photo' : 'hq24-no-photo' }}">
                                            <div class="hq24-photo-fallback" aria-label="{{ $photoFallbackLabel }}">
                                                <span class="hq24-photo-fallback-icon" aria-hidden="true"><i class="las la-user-shield"></i></span>
                                                <span class="hq24-photo-fallback-label">{{ $photoFallbackLabel }}</span>
                                            </div>
                                            @if ($hasVisiblePhoto)
                                                <img
                                                    src="{{ $photoUrl }}"
                                                    class="hq24-member-photo"
                                                    alt="{{ trim($user->first_name . ' ' . $user->last_name) }}"
                                                    loading="lazy"
                                                    decoding="async"
                                                    onerror="this.remove();">
                                            @endif

                                            <div class="hq-profile-badges">
                                                @if($isVerified)
                                                    <span class="hq-verified-tag"><i class="las la-certificate"></i> {{ translate('CNIC Verified') }}</span>
                                                @endif
                                                <span class="hq-active-tag {{ $isOnline ? 'is-online' : '' }}">
                                                    <i class="las la-circle"></i> {{ $isOnline ? translate('Online Now') : translate('Active Recently') }}
                                                </span>
                                            </div>

                                            <a id="shortlist_a_id_{{ $user->id }}"
                                                @if ($shortlist_onclick == 1)
                                                    onclick="do_shortlist({{ $user->id }})"
                                                @elseif ($shortlist_onclick == 0)
                                                    onclick="remove_shortlist({{ $user->id }})"
                                                @else
                                                    onclick="show_shortlist_requirement(@json($shortlist_prompt))"
                                                @endif
                                                class="hq-card-heart c-pointer" title="{{ $shortlist_text }}">
                                                <i class="{{ $shortlist ? 'las' : 'lar' }} la-heart"></i>
                                            </a>

                                            <div class="hq-discovery-photo-overlay">
                                                <div>
                                                    <h2>{{ $user->first_name . ' ' . $user->last_name }}</h2>
                                                    <small>ID: {{ $user->code }}</small>
                                                </div>
                                                @if($compatibility)
                                                    <span class="hq-match-pill"><i class="las la-heart"></i> {{ (int) $compatibility->match_percentage }}% {{ translate('Match') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="hq-discovery-body">
                                            <div class="hq-profile-facts">
                                                @if ($age)
                                                    <span><i class="las la-user"></i>{{ $age }} {{ translate('Years') }}</span>
                                                @endif
                                                @if ($height)
                                                    <span><i class="las la-ruler-vertical"></i>{{ $height }}</span>
                                                @endif
                                                @if ($locationText)
                                                    <span><i class="las la-map-marker"></i>{{ $locationText }}</span>
                                                @endif
                                            </div>

                                            @if($career?->designation || $education?->degree)
                                                <div class="hq-profile-profession">
                                                    <i class="las la-graduation-cap"></i>
                                                    <span>{{ $career?->designation ?: $education?->degree }}</span>
                                                    @if ($religionName)
                                                        <b>• {{ $religionName }}</b>
                                                    @endif
                                                </div>
                                            @endif

                                            <p class="hq-profile-summary">
                                                {{ \Illuminate\Support\Str::limit($compatibility?->compatibility_explanation ?: translate('A verified Hamqadam member seeking a respectful, family-minded lifelong partnership.'), 145) }}
                                            </p>

                                            <div class="hq-profile-tags">
                                                @foreach(array_filter([$languageName, $casteName, $maritalStatus]) as $tag)
                                                    <span>{{ $tag }}</span>
                                                @endforeach
                                                @if ($member?->trust_badge)
                                                    <span>{{ translate('Trust Badge') }}</span>
                                                @endif
                                            </div>

                                            <div class="hq-discovery-actions">
                                                @if($interest_onclick === 1)
                                                    <button type="button" id="interest_a_id_{{ $user->id }}" onclick="express_interest({{ $user->id }})" class="btn btn-primary">
                                                        <i class="lar la-heart"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </button>
                                                @elseif($interest_onclick === 'do_response')
                                                    <a id="interest_a_id_{{ $user->id }}" href="{{ route('interest_requests') }}" class="btn btn-primary">
                                                        <i class="las la-reply"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </a>
                                                @else
                                                    <button type="button" id="interest_a_id_{{ $user->id }}" class="btn btn-soft-primary" disabled>
                                                        <i class="las la-check"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </button>
                                                @endif

                                                @if($profileNeedsUpgrade)
                                                    <button type="button" onclick="package_update_alert()" class="btn btn-soft-primary"><i class="las la-user-lock"></i>{{ translate('View Profile') }}</button>
                                                @else
                                                    <a href="{{ route('member_profile', $user->id) }}" class="btn btn-soft-primary"><i class="las la-user"></i>{{ translate('View Profile') }}</a>
                                                @endif
                                            </div>

                                            <div class="hq-card-secondary-actions">
                                                <button type="button" onclick="ignore_member({{ $user->id }})"><i class="las la-ban"></i>{{ translate('Ignore') }}</button>
                                                <button type="button"
                                                    @if (! $profile_reported)
                                                        onclick="report_member({{ $user->id }})"
                                                    @endif
                                                    @disabled($profile_reported)>
                                                    <i class="las la-flag"></i>{{ $profile_reported ? translate('Reported') : translate('Report') }}
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="aiz-pagination">
                                {{ $users->appends(request()->input())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="hq24-upgrade-banner"><div><span class="hq24-upgrade-kicker"><i class="las la-award"></i> {{ translate('Dignified Matchmaking Privilege') }}</span><h2>{{ translate('Unlock Direct Contact Info & Family Introductions') }}</h2><p>{{ translate('Review available membership options for expanded introductions and respectful family connections.') }}</p></div><a class="btn btn-primary" href="{{ route('packages') }}">{{ translate('Explore Premium Plans') }} <i class="las la-arrow-right"></i></a></aside>
        </div>
    </section>
</div>