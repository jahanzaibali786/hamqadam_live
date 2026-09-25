@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-20 fw-700 mb-0">{{ translate('Guardian Panel') }}</h1>
            <p class="text-muted fs-12 mb-0">
                {{ translate('You are acting as a guardian — these are not your own matches. Every action is visible to the member.') }}
            </p>
        </div>
        <span class="badge badge-primary">{{ translate('Guardian Mode') }}</span>
    </div>

    <div class="row g-4 mt-1">
        {{-- Managed profiles --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Members You Assist') }}</h2></div>
                <div class="card-body">
                    @forelse($managed as $link)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong>{{ $link->profile?->first_name }} {{ $link->profile?->last_name }}</strong>
                                <div class="text-muted fs-12">{{ $link->relationship }} · {{ translate($link->guardian_role ?? 'guardian') }}</div>
                            </div>
                            <a href="{{ route('guardian_panel.matches', ['profile_user_id' => $link->profile_user_id]) }}"
                               class="btn btn-sm btn-outline-primary">{{ translate('Review Matches') }}</a>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('No approved guardian links yet. Ask the member to invite you.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Summary') }}</h2></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>{{ translate('Pending approvals') }}</span><strong>{{ $pendingApprovals }}</strong></div>
                    <div class="d-flex justify-content-between"><span>{{ translate('Members you assist') }}</span><strong>{{ $managed->count() }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Recent activity on the members --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Recent Guardian Activity') }}</h2></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            @forelse($recentActivity as $log)
                                <tr>
                                    <td class="fs-13 ps-3">{{ $log->profile?->first_name }}</td>
                                    <td class="fs-13">{{ translate(str_replace('_', ' ', ucfirst($log->action))) }}</td>
                                    <td class="text-muted fs-12 pe-3 text-end">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-muted text-center py-4">{{ translate('No activity yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
