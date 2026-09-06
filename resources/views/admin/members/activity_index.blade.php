@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('User Activity Tracking') }}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('Select a member to inspect activity') }}</h5>
        </div>
        <div class="col-md-6">
            <form id="sort_activity_members" action="{{ route('member.activity.index') }}" method="GET">
                <div class="row gutters-5">
                    <div class="col-md-5">
                        <select name="status" class="form-control form-control-sm aiz-selectpicker" onchange="this.form.submit()">
                            <option value="">{{ translate('All Status') }}</option>
                            <option value="active" @selected($status === 'active')>{{ translate('Active') }}</option>
                            <option value="inactive" @selected($status === 'inactive')>{{ translate('Inactive / Dormant') }}</option>
                            <option value="pending" @selected($status === 'pending')>{{ translate('Pending Approval') }}</option>
                            <option value="blocked" @selected($status === 'blocked')>{{ translate('Blocked') }}</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <input type="text" class="form-control form-control-sm" name="search" value="{{ $sortSearch }}" placeholder="{{ translate('Search name / email / phone / member code') }}">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Member') }}</th>
                    <th>{{ translate('Package') }}</th>
                    <th>{{ translate('Verification') }}</th>
                    <th>{{ translate('Last Seen') }}</th>
                    <th>{{ translate('Sessions') }}</th>
                    <th>{{ translate('Account Status') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $key => $member)
                    @php
                        $lastSeen = $member->activity_last_seen_at;
                    @endphp
                    <tr>
                        <td>{{ ($key + 1) + ($members->currentPage() - 1) * $members->perPage() }}</td>
                        <td>
                            <div class="fw-700">{{ trim($member->first_name.' '.$member->last_name) }}</div>
                            <div class="fs-12 text-muted">{{ $member->code }} · {{ $member->email ?: $member->phone }}</div>
                        </td>
                        <td>{{ $member->member?->package?->name ?? translate('No active package') }}</td>
                        <td>
                            @if($member->approved)
                                <span class="badge badge-inline badge-success">{{ translate('Approved') }}</span>
                            @else
                                <span class="badge badge-inline badge-warning">{{ translate('Pending') }}</span>
                            @endif
                        </td>
                        <td>{{ $lastSeen ? $lastSeen->diffForHumans() : translate('Not tracked yet') }}</td>
                        <td>{{ (int) ($member->activity_session_count ?? 0) }}</td>
                        <td>
                            @if($member->blocked)
                                <span class="badge badge-inline badge-danger">{{ translate('Blocked') }}</span>
                            @elseif($member->deactivated)
                                <span class="badge badge-inline badge-secondary">{{ translate('Deactivated') }}</span>
                            @else
                                <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('member.activity', encrypt($member->id)) }}" class="btn btn-sm btn-soft-primary">{{ translate('View Activity') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $members->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection




