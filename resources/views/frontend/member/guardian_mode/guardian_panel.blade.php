@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="fs-22 fw-700 mb-1 text-primary">{{ translate('Guardian Dashboard') }}</h1>
            <p class="text-muted fs-13 mb-0">
                {{ translate('You are acting on behalf of your family member. All match reviews and coin usages use the member\'s account.') }}
            </p>
        </div>
        <span class="badge badge-primary px-3 py-2 fs-12">{{ translate('Guardian Mode Active') }}</span>
    </div>

    <div class="row g-4 mt-1">
        {{-- Managed Profiles Detailed Cards --}}
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                    <h2 class="fs-16 fw-700 mb-0"><i class="las la-user-check text-primary mr-1"></i> {{ translate('Family Members You Assist') }}</h2>
                    <span class="badge badge-soft-primary">{{ $managed->count() }} {{ translate('Active Profile(s)') }}</span>
                </div>
                <div class="card-body p-3">
                    @forelse($managed as $link)
                        @php
                            $memberUser = $link->profile;
                            $memberData = $memberUser?->member;
                            $pkgName = $memberData?->package?->name ?? translate('Free Plan');
                            $coins = $memberData?->remaining_interest ?? 0;
                            $linkPermissions = is_array($link->permissions) ? $link->permissions : [];
                        @endphp
                        <div class="border rounded-lg p-3 mb-3 bg-light-subtle shadow-none">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md mr-3 border">
                                        @if ($memberUser?->photo)
                                            <img src="{{ uploaded_asset($memberUser->photo) }}" class="rounded-circle">
                                        @else
                                            <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="rounded-circle">
                                        @endif
                                    </span>
                                    <div>
                                        <h3 class="fs-16 fw-700 mb-0 text-dark">{{ $memberUser?->first_name }} {{ $memberUser?->last_name }}</h3>
                                        <div class="fs-12 text-muted">
                                            <span class="badge badge-soft-dark mr-1">{{ $link->relationship }}</span>
                                            @if ($link->is_wali)
                                                <span class="badge badge-soft-success"><i class="las la-shield-alt"></i> {{ translate('Wali') }}</span>
                                            @endif
                                            <span>· {{ translate($link->guardian_role ?? 'Guardian') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('guardian_panel.matches', ['profile_user_id' => $link->profile_user_id]) }}"
                                       class="btn btn-primary btn-sm px-3 shadow-sm">
                                        <i class="las la-user-friends mr-1"></i> {{ translate('Review Matches') }}
                                    </a>
                                </div>
                            </div>

                            {{-- Quota & Package details of the member --}}
                            <div class="bg-white p-2 rounded border mb-3">
                                <div class="row text-center text-sm-left">
                                    <div class="col-sm-6 mb-2 mb-sm-0 border-sm-right">
                                        <span class="fs-11 text-muted d-block text-uppercase">{{ translate('Member Active Package') }}</span>
                                        <strong class="fs-13 text-dark"><i class="las la-gem text-warning mr-1"></i> {{ $pkgName }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="fs-11 text-muted d-block text-uppercase">{{ translate('Available Coin Quota') }}</span>
                                        <strong class="fs-13 text-success"><i class="las la-coins mr-1"></i> {{ $coins }} {{ translate('Coins') }}</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Active Permissions summary --}}
                            <div class="mt-2">
                                <span class="fs-11 text-muted fw-600 d-block mb-1">{{ translate('Your Granted Permissions:') }}</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @if(in_array('view_recommended_matches', $linkPermissions))
                                        <span class="badge badge-inline badge-soft-primary fs-11 mr-1 mb-1"><i class="las la-check mr-1"></i> {{ translate('Review Matches') }}</span>
                                    @endif
                                    @if(in_array('shortlist_match', $linkPermissions))
                                        <span class="badge badge-inline badge-soft-primary fs-11 mr-1 mb-1"><i class="las la-check mr-1"></i> {{ translate('Shortlist') }}</span>
                                    @endif
                                    @if(in_array('recommend_match', $linkPermissions))
                                        <span class="badge badge-inline badge-soft-primary fs-11 mr-1 mb-1"><i class="las la-check mr-1"></i> {{ translate('Recommend') }}</span>
                                    @endif
                                    @if(in_array('add_guardian_note', $linkPermissions))
                                        <span class="badge badge-inline badge-soft-primary fs-11 mr-1 mb-1"><i class="las la-check mr-1"></i> {{ translate('Add Notes') }}</span>
                                    @endif
                                    @if(in_array('approve_family_intro', $linkPermissions))
                                        <span class="badge badge-inline badge-soft-success fs-11 mr-1 mb-1"><i class="las la-check mr-1"></i> {{ translate('Family Intro') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        @if(isset($paused) && $paused->isNotEmpty())
                            <div class="py-2">
                                <div class="alert alert-soft-warning border-0 p-3 rounded text-start mb-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="las la-pause-circle text-warning fs-24 me-2"></i>
                                        <h3 class="fs-15 fw-700 text-dark mb-0">{{ translate('Guardian Access Temporarily Paused') }}</h3>
                                    </div>
                                    <p class="fs-13 text-muted mb-3">
                                        {{ translate('Your family member has temporarily paused guardian access. Match reviews and delegated actions are suspended until they resume your access.') }}
                                    </p>
                                    @foreach($paused as $pLink)
                                        <div class="bg-white p-2 rounded border d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>{{ $pLink->profile?->first_name }} {{ $pLink->profile?->last_name }}</strong>
                                                <span class="badge badge-inline badge-warning ms-1">{{ translate('Paused by Member') }}</span>
                                            </div>
                                            <span class="fs-12 text-muted">{{ $pLink->relationship }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="las la-user-shield la-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">{{ translate('No approved guardian links yet. When a member invites you, your assisted profile will appear here.') }}</p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>

            {{-- Accept another invitation code --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h2 class="fs-16 fw-700 mb-0"><i class="las la-key text-primary mr-1"></i> {{ translate('Connect With Another Member') }}</h2></div>
                <div class="card-body">
                    <p class="fs-12 text-muted mb-2">{{ translate('If another family member sent you an invitation token or code, enter it below to link their profile:') }}</p>
                    <form action="{{ route('guardian_mode.web_accept_form') }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="token" class="form-control form-control-sm" placeholder="{{ translate('Enter 48-digit invitation code') }}" required>
                            <button class="btn btn-sm btn-primary" type="submit">{{ translate('Connect') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Quick Stats & Recent Activity --}}
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h2 class="fs-16 fw-700 mb-0">{{ translate('Quick Overview') }}</h2></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted"><i class="las la-users mr-1"></i> {{ translate('Members Assisted') }}</span>
                        <strong>{{ $managed->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted"><i class="las la-clock mr-1"></i> {{ translate('Pending Approvals') }}</span>
                        <strong class="text-primary">{{ $pendingApprovals }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted"><i class="las la-handshake mr-1"></i> {{ translate('Family Introductions') }}</span>
                        <a href="{{ route('guardian_mode.introductions') }}" class="btn btn-xs btn-outline-primary">{{ translate('View') }}</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="fs-16 fw-700 mb-0"><i class="las la-history text-primary mr-1"></i> {{ translate('Recent Activity') }}</h2>
                    <a href="{{ route('guardian_mode.activity') }}" class="btn btn-xs btn-link">{{ translate('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            @forelse($recentActivity as $log)
                                <tr>
                                    <td class="fs-13 ps-3"><strong>{{ $log->profile?->first_name }}</strong></td>
                                    <td class="fs-12 text-muted">{{ translate(str_replace('_', ' ', ucfirst($log->action))) }}</td>
                                    <td class="text-muted fs-11 pe-3 text-end">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-muted text-center py-4" colspan="3">{{ translate('No activity recorded yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
