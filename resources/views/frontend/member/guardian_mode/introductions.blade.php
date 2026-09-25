@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="fs-20 fw-700 mb-0">{{ translate('Family Introductions') }}</h1>
        <a href="{{ route('guardian_mode.index') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Back to Guardian Mode') }}</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><h2 class="fs-16 mb-0">{{ translate('Request a Family Introduction') }}</h2></div>
        <div class="card-body">
            <p class="text-muted fs-12 mb-2">
                {{ translate('Available on your accepted proposals. Both families must consent before a family conversation opens. Personal chats are never shared.') }}
            </p>
            <form method="POST" action="{{ route('guardian_mode.introductions.store') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <select name="proposal_id" class="form-select" required>
                        <option value="">{{ translate('Select an accepted proposal') }}</option>
                        @foreach (\App\Models\ExpressInterest::where(function ($q) { $q->where('user_id', auth()->id())->orWhere('interested_by', auth()->id()); })->where('status', 'accepted')->get() as $interest)
                            <option value="{{ $interest->id }}">#{{ $interest->id }} — {{ \App\Models\User::find($interest->user_id === auth()->id() ? $interest->interested_by : $interest->user_id)?->first_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="message" class="form-control" placeholder="{{ translate('A short message for the other family (optional)') }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100">{{ translate('Request Introduction') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            @forelse($introductions as $introduction)
                <div class="d-flex justify-content-between align-items-center border-bottom p-3">
                    <div>
                        <strong>{{ translate('Proposal') }} #{{ $introduction->proposal_id }}</strong>
                        <span class="badge badge-inline
                            {{ $introduction->status === 'active' ? 'badge-success' : ($introduction->status === 'requested' ? 'badge-warning' : ($introduction->status === 'declined' || $introduction->status === 'cancelled' ? 'badge-danger' : 'badge-secondary')) }}">
                            {{ translate(ucfirst($introduction->status)) }}
                        </span>
                        <div class="text-muted fs-12">{{ $introduction->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        @if ($introduction->status === 'requested')
                            <form method="POST" action="{{ route('guardian_mode.introductions.respond', $introduction->id) }}">
                                @csrf
                                <input type="hidden" name="accept" value="1">
                                <button class="btn btn-sm btn-success">{{ translate('Accept') }}</button>
                            </form>
                            <form method="POST" action="{{ route('guardian_mode.introductions.respond', $introduction->id) }}">
                                @csrf
                                <input type="hidden" name="accept" value="0">
                                <button class="btn btn-sm btn-outline-danger">{{ translate('Decline') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-4 mb-0">{{ translate('No family introductions yet.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $introductions->links() }}</div>
@endsection
