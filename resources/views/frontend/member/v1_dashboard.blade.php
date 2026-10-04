@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('AI Matrimonial Dashboard') }}</h1>
                <p class="mb-0 opacity-70">
                    {{ translate('Your compatibility, verification, safety, and AI activity in one place.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('profile_settings') }}" class="btn btn-soft-primary">
                    <i class="las la-user-edit mr-1"></i>{{ translate('Improve Profile') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-10 mb-3">
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('profile_settings') }}" class="card shadow-sm text-reset text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="las la-chart-pie la-2x text-primary mb-2"></i>
                    <div class="h3 fw-700 mb-0">{{ $profileCompletion }}%</div>
                    <div class="opacity-70">{{ translate('Profile Completion') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('member.verification') }}" class="card shadow-sm text-reset text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="las la-shield-alt la-2x text-success mb-2"></i>
                    <div class="h5 fw-700 mb-1">
                        {{ translate(ucwords(str_replace('_', ' ', $verificationStatus ?? 'not_submitted'))) }}
                    </div>
                    <div class="opacity-70">{{ translate('Verification') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="#" class="card shadow-sm text-reset text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="las la-heart la-2x text-danger mb-2"></i>
                    <div class="h3 fw-700 mb-0">{{ $proposalStats['received_pending'] }}</div>
                    <div class="opacity-70">{{ translate('Pending Requests') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="#" class="card shadow-sm text-reset text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="las la-magic la-2x text-info mb-2"></i>
                    <div class="h3 fw-700 mb-0">{{ $recentAiRequests->count() }}</div>
                    <div class="opacity-70">{{ translate('Recent AI Actions') }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row gutters-10">
        <div class="col-xl-12">
            <div class="card shadow-sm border border-success">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <div class="opacity-70 fs-12">{{ translate('Current Package') }}</div>
                            <div class="h5 mb-1">{{ $currentPackage?->name ?? translate('No package active') }}</div>
                            <div class="fs-13">
                                {{ translate('Coin balance') }}:
                                <strong>{{ get_remaining_package_value(Auth::id(), 'remaining_interest') }}</strong>
                                <span class="mx-1">|</span>
                                {{ translate('Valid until') }}:
                                <strong>{{ Auth::user()->member?->package_validity ?: translate('N/A') }}</strong>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <div class="opacity-70 fs-12">{{ translate('Latest Payment') }}</div>
                            @if ($latestPayment)
                                <div class="h6 mb-1">{{ $latestPayment->package?->name ?? translate('Package') }}</div>
                                <span
                                    class="badge badge-inline {{ $latestPayment->payment_status === 'Paid' ? 'badge-success' : 'badge-warning' }}">
                                    {{ translate($latestPayment->payment_status) }}
                                </span>
                                <span
                                    class="fs-13 ml-2">{{ ucfirst(str_replace('_', ' ', $latestPayment->payment_method)) }}</span>
                            @else
                                <div class="text-muted">{{ translate('No payment history yet') }}</div>
                            @endif
                        </div>
                        <div class="col-lg-4 text-lg-right">
                            @if ($recommendedPackage)
                                <div class="opacity-70 fs-12">{{ translate('Recommended Next') }}</div>
                                <div class="h6 mb-2">{{ $recommendedPackage->name }} -
                                    {{ single_price($recommendedPackage->price) }}</div>
                                <a href="{{ route('package_payment_methods', encrypt($recommendedPackage->id)) }}"
                                    class="btn btn-sm btn-success">
                                    {{ translate('Upgrade Package') }}
                                </a>
                            @else
                                <a href="{{ route('packages') }}"
                                    class="btn btn-sm btn-success">{{ translate('View Packages') }}</a>
                            @endif
                            <a href="{{ route('package_purchase_history') }}" class="btn btn-sm btn-outline-secondary">
                                {{ translate('Payment History') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="fs-16 mb-0">{{ translate('Top Compatibility Matches') }}</h2>
                    <a href="{{ route('my_matched_profiles') }}"
                        class="btn btn-sm btn-soft-primary">{{ translate('View All') }}</a>
                </div>
                <div class="card-body">
                    @forelse($topMatches as $match)
                        @php $matchedUser = $match->matchedUser; @endphp
                        <div class="d-flex align-items-start border-bottom pb-3 mb-3" style="min-width: 0;">
                            <div class="avatar avatar-md mr-3 flex-shrink-0">
                                @if ($matchedUser?->photo)
                                    <img src="{{ uploaded_asset($matchedUser->photo) }}">
                                @else
                                    <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                                @endif
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <a href="{{ $matchedUser ? route('member_profile', $matchedUser->id) : '#' }}"
                                    class="text-dark fw-600">
                                    {{ $matchedUser ? trim($matchedUser->first_name . ' ' . $matchedUser->last_name) : translate('Profile unavailable') }}
                                </a>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-primary"
                                        style="width: {{ (int) $match->match_percentage }}%"></div>
                                </div>
                                <div class="fs-12 opacity-70 mt-1">
                                    {{ $match->compatibility_explanation ?: translate('Compatibility explanation will appear after recalculation.') }}
                                </div>
                                @if (!empty($match->compatibility_summary['facts']))
                                    <div class="d-flex flex-wrap mt-2" style="gap: 6px;">
                                        @foreach ($match->compatibility_summary['facts'] as $fact)
                                            <span class="text-muted" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border: 1px solid #e6d7df; border-radius: 999px; background: #fff8fb; font-size: 11px; line-height: 1.35; white-space: normal;">
                                                <i class="las la-check-circle mr-1"></i>{{ $fact[0] }}: {{ $fact[1] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if (!empty($match->compatibility_summary['matched_criteria']))
                                    <div class="fs-12 fw-600 mt-2 mb-1">{{ translate('Matched preferences') }}</div>
                                    <div class="d-flex flex-wrap" style="gap: 6px;">
                                        @foreach ($match->compatibility_summary['matched_criteria'] as $criterion)
                                            <span title="{{ $criterion['reason'] ?? '' }}" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border: 1px solid #cde9dc; border-radius: 999px; background: #f3fff8; color: #28784c; font-size: 11px; line-height: 1.35; white-space: normal;">
                                                <i class="las la-heart mr-1"></i>{{ $criterion['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="ml-3 h5 mb-0 flex-shrink-0" style="min-width: 42px; text-align: right;">{{ (int) $match->match_percentage }}%</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            {{ translate('No AI compatibility matches yet. Refresh matches from the API or complete your profile first.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card shadow-sm border border-primary">
                <div class="card-header">
                    <h2 class="fs-16 mb-0">{{ translate('AI Matching Engine') }}</h2>
                </div>
                <div class="card-body">
                    <p class="opacity-70 mb-3">
                        {{ translate('Compatibility is calculated from religion, lifestyle, education, profession, income, age, prayer, language, location, and behavior signals.') }}
                    </p>
                    <a href="{{ route('match.refresh') }}" class="btn btn-primary btn-sm">
                        <i class="las la-sync mr-1"></i>{{ translate('Recalculate Matches') }}
                    </a>
                    <a href="{{ route('my_matched_profiles') }}" class="btn btn-outline-primary btn-sm">
                        {{ translate('View Matched Profiles') }}
                    </a>
                    <a href="{{ route('member.listing') }}" class="btn btn-soft-info btn-sm">
                        {{ translate('Browse Search') }}
                    </a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fs-16 mb-0">{{ translate('Proposal Summary') }}</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Sent Pending') }}</span>
                        <strong>{{ $proposalStats['sent_pending'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Received Pending') }}</span>
                        <strong>{{ $proposalStats['received_pending'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ translate('Accepted') }}</span>
                        <strong>{{ $proposalStats['accepted'] }}</strong>
                    </div>
                    <a href="{{ route('interest_requests') }}"
                        class="btn btn-sm btn-soft-primary">{{ translate('Open Requests') }}</a>
                    <a href="{{ route('my_interests.index') }}"
                        class="btn btn-sm btn-outline-secondary">{{ translate('My Interests') }}</a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fs-16 mb-0">{{ translate('Safety & Family') }}</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Safety actions') }}</span>
                        <strong>{{ $safetyActions->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Guardian links') }}</span>
                        <strong>{{ $familyLinks->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ translate('Saved searches') }}</span>
                        <strong>{{ $savedSearches->count() }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="fs-16 mb-0">{{ translate('Recent AI Activity') }}</h2>
        </div>
        <div class="card-body">
            @forelse($recentAiRequests as $aiRequest)
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span>{{ str_replace('_', ' ', ucfirst((string) $aiRequest->feature)) }}</span>
                    <span class="badge badge-inline badge-soft-info">{{ $aiRequest->status }}</span>
                </div>
            @empty
                <div class="text-muted">
                    {{ translate('No AI requests yet. Use the v1 AI APIs for bio generation, match explanations, ice breakers, and profile quality checks.') }}
                </div>
            @endforelse
        </div>
    </div>
@endsection
