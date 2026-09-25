@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fs-20 fw-700 mb-0">{{ translate('Guardian Mode & Family') }}</h1>
        <form method="POST" action="{{ route('guardian_mode.toggle') }}">
            @csrf
            <input type="hidden" name="enabled" value="{{ $guardianModeEnabled ? '0' : '1' }}">
            <button type="submit" class="btn btn-sm {{ $guardianModeEnabled ? 'btn-outline-danger' : 'btn-primary' }}">
                {{ $guardianModeEnabled ? translate('Guardian Mode: ON — Turn Off') : translate('Guardian Mode: OFF — Turn On') }}
            </button>
        </form>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        {{-- Guardian list --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Your Guardians') }}</h2></div>
                <div class="card-body">
                    @forelse($links as $link)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                            <div>
                                <strong>{{ $link->guardian?->first_name }} {{ $link->guardian?->last_name }}</strong>
                                <span class="badge badge-inline
                                    {{ $link->status === 'approved' && ! $link->paused_at && ! $link->revoked_at ? 'badge-success' : ($link->status === 'revoked' ? 'badge-danger' : 'badge-secondary') }}">
                                    {{ $link->revoked_at ? translate('Revoked') : ($link->paused_at ? translate('Paused') : ($link->status === 'approved' ? translate('Active') : translate(ucfirst($link->status)))) }}
                                </span>
                                <div class="text-muted fs-12">
                                    {{ translate('Relationship') }}: {{ $link->relationship ?? '—' }}
                                    @if ($link->is_wali) · {{ translate('Wali') }} @endif
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('guardian_mode.permissions.edit', $link->id) }}" class="btn btn-sm btn-outline-primary">{{ translate('Permissions') }}</a>
                                @if ($link->status === 'approved' && ! $link->revoked_at)
                                    <form method="POST" action="{{ route('guardian_mode.lifecycle', [$link->id, $link->paused_at ? 'resume' : 'pause']) }}" onsubmit="return confirm('{{ translate('Are you sure?') }}')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">{{ $link->paused_at ? translate('Resume') : translate('Pause') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('guardian_mode.lifecycle', [$link->id, 'revoke']) }}" onsubmit="return confirm('{{ translate('Revoke this guardian? Access ends immediately.') }}')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">{{ translate('Revoke') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('No guardians yet. Invite a trusted family member below.') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Pending invitations --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Pending Invitations') }}</h2></div>
                <div class="card-body">
                    @forelse($invitations as $invitation)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                {{ $invitation->contact }} — {{ $invitation->relationship }}
                                <div class="text-muted fs-12">{{ translate('Expires') }}: {{ $invitation->expires_at->format('d M Y') }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <code class="fs-12">{{ $invitation->token }}</code>
                                <form method="POST" action="{{ route('guardian_mode.invitations.revoke', $invitation->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">{{ translate('Revoke') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('No pending invitations.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Invite form --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Invite a Guardian') }}</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('guardian_mode.invitations.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Guardian Phone or Email') }}</label>
                            <input type="text" name="contact" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Relationship') }}</label>
                            <select name="relationship" class="form-select" required>
                                @foreach (['Mother', 'Father', 'Brother', 'Sister', 'Uncle', 'Aunt', 'Wali', 'Other Guardian'] as $rel)
                                    <option value="{{ $rel }}">{{ translate($rel) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Guardian Role') }}</label>
                            <select name="guardian_role" class="form-select">
                                <option value="primary">{{ translate('Primary Guardian') }}</option>
                                <option value="supporting">{{ translate('Supporting Guardian') }}</option>
                                <option value="custom">{{ translate('Custom Guardian') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Permission Preset') }}</label>
                            <select name="permission_preset" class="form-select" id="presetSelect">
                                <option value="view_only">{{ translate('View Only — profile, verification, selected matches') }}</option>
                                <option value="review">{{ translate('Review — + shortlist, notes, proposal review') }}</option>
                                <option value="participate">{{ translate('Participate — + recommend, family-stage actions') }}</option>
                                <option value="custom">{{ translate('Custom — choose every permission') }}</option>
                            </select>
                            <div class="form-text">{{ translate('You can fine-tune every permission after the guardian accepts.') }}</div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_wali" value="1" id="waliCheck">
                            <label class="form-check-label" for="waliCheck">{{ translate('This guardian is my Wali') }}</label>
                        </div>
                        <button class="btn btn-primary w-100">{{ translate('Send Invitation') }}</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h3 class="fs-14 fw-700">{{ translate('Guardian Mode means:') }}</h3>
                    <ul class="fs-12 text-muted ps-3 mb-0">
                        <li>{{ translate('Guardians assist you — they never own your account.') }}</li>
                        <li>{{ translate('Private chats, contacts and documents stay hidden by default.') }}</li>
                        <li>{{ translate('Every guardian action is logged and visible to you.') }}</li>
                    </ul>
                    <a class="btn btn-sm btn-outline-secondary mt-2" href="{{ route('guardian_mode.activity') }}">{{ translate('View guardian activity log') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
