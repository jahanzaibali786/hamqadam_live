@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="fs-20 fw-700 mb-0">{{ translate('Match Review') }}</h1>
            <p class="text-muted fs-12 mb-0">
                {{ translate('Reviewing matches for') }} <strong>{{ $link->profile?->first_name }} {{ $link->profile?->last_name }}</strong>
                — {{ translate('recommendations only; the final decision is always theirs.') }}
            </p>
        </div>
        <a href="{{ route('guardian_panel.index') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Back') }}</a>
    </div>

    <div class="row g-3">
        @forelse($matches as $match)
            @php
                $target = $match->matchedUser;
                $feedbackRows = $feedback->get($match->match_id);
            @endphp
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $target?->first_name }} {{ $target?->last_name }}</strong>
                            <span class="badge badge-success">{{ $match->match_percentage }}%</span>
                        </div>
                        <div class="text-muted fs-12 mb-2">
                            {{ $target?->member?->city_id ?? '' }}
                        </div>

                        @if ($match->compatibility_explanation)
                            <p class="fs-12 mb-2">{{ Str::limit($match->compatibility_explanation, 140) }}</p>
                        @endif

                        @if ($feedbackRows && $feedbackRows->count())
                            <div class="mb-2">
                                @foreach ($feedbackRows as $row)
                                    <span class="badge badge-inline {{ $row->feedback_type === 'recommend' ? 'badge-success' : ($row->feedback_type === 'note' ? 'badge-secondary' : 'badge-warning') }}">
                                        {{ translate(str_replace('_', ' ', ucfirst($row->feedback_type))) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('guardian_panel.feedback') }}" class="row g-2 align-items-center">
                            @csrf
                            <input type="hidden" name="profile_user_id" value="{{ $link->profile_user_id }}">
                            <input type="hidden" name="target_user_id" value="{{ $match->match_id }}">
                            <div class="col-auto">
                                <select name="action" class="form-select form-select-sm">
                                    <option value="shortlist">{{ translate('Shortlist') }}</option>
                                    <option value="recommend">{{ translate('Recommend') }}</option>
                                    <option value="not_suitable">{{ translate('Not Suitable') }}</option>
                                    <option value="note">{{ translate('Add Note') }}</option>
                                </select>
                            </div>
                            <div class="col">
                                <input type="text" name="comment" class="form-control form-control-sm" placeholder="{{ translate('Note / reason (optional)') }}">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center py-4">{{ translate('No recommended matches for this member yet.') }}</p>
        @endforelse
    </div>
@endsection
