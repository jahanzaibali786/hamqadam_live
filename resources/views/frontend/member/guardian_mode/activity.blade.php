@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="fs-20 fw-700 mb-0">{{ translate('Guardian Activity Log') }}</h1>
        <a href="{{ route('guardian_mode.index') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Back to Guardian Mode') }}</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('When') }}</th>
                        <th>{{ translate('Who') }}</th>
                        <th>{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted fs-12">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="fs-13">{{ $log->guardian ? $log->guardian->first_name . ' ' . $log->guardian->last_name : '—' }}</td>
                            <td class="fs-13">{{ translate(str_replace('_', ' ', ucfirst($log->action))) }}
                                @if ($log->metadata && isset($log->metadata['reason']))
                                    <span class="text-muted">({{ translate($log->metadata['reason']) }})</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-4">{{ translate('No guardian activity yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
