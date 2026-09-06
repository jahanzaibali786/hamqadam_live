@extends('admin.layouts.app')

@section('content')
@php
    $name = trim($user->first_name.' '.$user->last_name);
    $fmt = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('d M Y, h:i A') : translate('Not available');
    $statusText = function ($value) {
        if ($value instanceof \App\Enums\ProposalStatus) {
            return translate(ucfirst($value->label()));
        }

        if ($value instanceof \BackedEnum) {
            return translate(ucfirst(str_replace('_', ' ', (string) $value->value)));
        }

        if (is_numeric($value)) {
            $proposalStatus = \App\Enums\ProposalStatus::tryFrom((int) $value);
            if ($proposalStatus) {
                return translate(ucfirst($proposalStatus->label()));
            }
        }

        return translate(ucfirst(str_replace('_', ' ', (string) $value)));
    };
@endphp

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-1">{{ translate('User Activity Tracking') }}</h1>
            <div class="text-muted fs-13">{{ $name }} · {{ $user->code }} · {{ $user->email }}</div>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('members.show', encrypt($user->id)) }}" class="btn btn-sm btn-soft-primary">{{ translate('View Profile') }}</a>
            <a href="{{ route('filterbyStatus', 'pending') }}" class="btn btn-sm btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="row gutters-10">
    @foreach([
        ['Registration', $fmt($summary['registered_at']), 'la-user-plus'],
        ['Last Login', $fmt($summary['last_login_at']), 'la-sign-in-alt'],
        ['Last Seen', $fmt($summary['last_seen_at']), 'la-clock'],
        ['Sessions', $summary['session_count'], 'la-layer-group'],
        ['Activity Status', $summary['active_status'], 'la-heartbeat'],
        ['Location / IP', ($summary['latest_location'] ?: translate('Unknown')).($summary['latest_ip'] ? ' · '.$summary['latest_ip'] : ''), 'la-map-marker'],
        ['Verification', $summary['verification_status'], 'la-id-card'],
        ['Trust Badge', $summary['trust_badge_status'], 'la-certificate'],
        ['Package', $summary['package_name'], 'la-box'],
    ] as $item)
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="size-45px rounded bg-soft-primary text-primary d-flex align-items-center justify-content-center mr-3">
                        <i class="las {{ $item[2] }} la-2x"></i>
                    </div>
                    <div class="minw-0">
                        <div class="text-muted fs-12">{{ translate($item[0]) }}</div>
                        <div class="fw-700 text-truncate">{{ $item[1] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Matchmaking Journey') }}</h5></div>
    <div class="card-body">
        <div class="row gutters-10">
            @foreach($journey as $step)
                <div class="col-xl col-md-4 col-6 mb-3">
                    <div class="border rounded p-3 h-100 {{ $step['status'] === 'complete' ? 'border-success' : 'border-warning' }}">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-inline {{ $step['status'] === 'complete' ? 'badge-success' : 'badge-warning' }} mr-2">{{ translate($step['status']) }}</span>
                        </div>
                        <div class="fw-700 fs-13">{{ translate($step['label']) }}</div>
                        <div class="text-muted fs-12 mt-1">{{ $step['detail'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Troubleshooting Diagnosis') }}</h5></div>
            <div class="card-body">
                <div class="row gutters-5 mb-3">
                    <div class="col-6"><div class="bg-light rounded p-2 text-center"><div class="h5 mb-0">{{ $diagnostics['eligible_profile_count'] }}</div><div class="fs-11 text-muted">{{ translate('Eligible Profiles') }}</div></div></div>
                    <div class="col-6"><div class="bg-light rounded p-2 text-center"><div class="h5 mb-0">{{ $diagnostics['match_count'] }}</div><div class="fs-11 text-muted">{{ translate('Matches') }}</div></div></div>
                    <div class="col-6 mt-2"><div class="bg-light rounded p-2 text-center"><div class="h5 mb-0">{{ $diagnostics['shortlist_count'] }}</div><div class="fs-11 text-muted">{{ translate('Shortlists') }}</div></div></div>
                    <div class="col-6 mt-2"><div class="bg-light rounded p-2 text-center"><div class="h5 mb-0">{{ $diagnostics['sent_request_count'] }}</div><div class="fs-11 text-muted">{{ translate('Requests Sent') }}</div></div></div>
                </div>
                @foreach($diagnostics['issues'] as $issue)
                    <div class="alert alert-{{ $issue['level'] }} py-2 mb-2">
                        <div class="fw-700">{{ translate($issue['title']) }}</div>
                        <div class="fs-12">{{ translate($issue['detail']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Login & Session Activity') }}</h5></div>
            <div class="card-body table-responsive">
                <div class="alert alert-info py-2 fs-12">
                    {{ translate('If IP shows ::1, 127.0.0.1, or Private network, Laravel is only receiving the local/proxy IP. Configure your web server/CDN to pass CF-Connecting-IP, X-Real-IP, or X-Forwarded-For for real visitor IP tracking.') }}
                </div>
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('When') }}</th><th>{{ translate('Type') }}</th><th>{{ translate('Device') }}</th><th>{{ translate('Location / IP') }}</th><th>{{ translate('IP Source') }}</th></tr></thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td>{{ $fmt($log->occurred_at) }}</td>
                                <td>{{ translate(ucfirst(str_replace('_', ' ', $log->event_type))) }}</td>
                                <td>{{ $log->device_type ?: translate('Unknown') }}</td>
                                <td>{{ $log->location ?: translate('Unknown') }} @if($log->ip_address)<span class="text-muted">· {{ $log->ip_address }}</span>@endif</td>
                                <td class="fs-12">{{ data_get($log->metadata, 'ip_source', 'request_ip') }} @if(data_get($log->metadata, 'proxy_ip') && data_get($log->metadata, 'proxy_ip') !== $log->ip_address)<span class="text-muted">· proxy {{ data_get($log->metadata, 'proxy_ip') }}</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No tracked login activity yet. New logins will appear here.') }}</td></tr>
                        @endforelse
                        @foreach($deviceSessions as $session)
                            <tr>
                                <td>{{ $fmt($session->last_used_at) }}</td>
                                <td>{{ translate('Mobile/API Session') }}</td>
                                <td>{{ $session->device_name ?: $session->device_type ?: translate('Unknown') }}</td>
                                <td>{{ $session->ip_address ?: translate('Unknown') }}</td>
                                <td>{{ translate('device_session') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Shortlisted Profiles') }}</h5></div>
            <div class="card-body table-responsive">
                <div class="alert alert-info py-2 fs-12">
                    {{ translate('If IP shows ::1, 127.0.0.1, or Private network, Laravel is only receiving the local/proxy IP. Configure your web server/CDN to pass CF-Connecting-IP, X-Real-IP, or X-Forwarded-For for real visitor IP tracking.') }}
                </div>
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Profile') }}</th><th>{{ translate('Code') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                    <tbody>
                        @forelse($shortlists as $row)
                            <tr><td>{{ trim(($row->user?->first_name ?? '').' '.($row->user?->last_name ?? '')) ?: translate('Deleted user') }}</td><td>{{ $row->user?->code }}</td><td>{{ $fmt($row->created_at) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">{{ translate('No shortlist activity.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Requests / Proposals Sent') }}</h5></div>
            <div class="card-body table-responsive">
                <div class="alert alert-info py-2 fs-12">
                    {{ translate('If IP shows ::1, 127.0.0.1, or Private network, Laravel is only receiving the local/proxy IP. Configure your web server/CDN to pass CF-Connecting-IP, X-Real-IP, or X-Forwarded-For for real visitor IP tracking.') }}
                </div>
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Recipient') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                    <tbody>
                        @forelse($sentProposals as $row)
                            <tr><td>{{ trim(($row->recipient?->first_name ?? '').' '.($row->recipient?->last_name ?? '')) ?: translate('Deleted user') }}</td><td><span class="badge badge-inline badge-info">{{ $statusText($row->status) }}</span></td><td>{{ $fmt($row->created_at) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">{{ translate('No sent proposals.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row gutters-10">
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Profile Views Made') }}</h5></div>
            <div class="card-body table-responsive">
                <div class="alert alert-info py-2 fs-12">
                    {{ translate('If IP shows ::1, 127.0.0.1, or Private network, Laravel is only receiving the local/proxy IP. Configure your web server/CDN to pass CF-Connecting-IP, X-Real-IP, or X-Forwarded-For for real visitor IP tracking.') }}
                </div>
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Viewed Profile') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                    <tbody>
                        @forelse($profileViewsMade as $row)
                            <tr><td>{{ trim(($row->user?->first_name ?? '').' '.($row->user?->last_name ?? '')) ?: translate('Deleted user') }}</td><td>{{ $fmt($row->created_at) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">{{ translate('No profile views made.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Package History') }}</h5></div>
            <div class="card-body table-responsive">
                <div class="alert alert-info py-2 fs-12">
                    {{ translate('If IP shows ::1, 127.0.0.1, or Private network, Laravel is only receiving the local/proxy IP. Configure your web server/CDN to pass CF-Connecting-IP, X-Real-IP, or X-Forwarded-For for real visitor IP tracking.') }}
                </div>
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Package') }}</th><th>{{ translate('Amount') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                    <tbody>
                        @forelse($packagePayments as $row)
                            <tr><td>{{ $row->package?->name ?? translate('Unknown') }}</td><td>{{ single_price($row->amount ?? 0) }}</td><td>{{ ucfirst((string) ($row->payment_status ?? $row->status ?? '')) }}</td><td>{{ $fmt($row->created_at) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ translate('No package purchases.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection



