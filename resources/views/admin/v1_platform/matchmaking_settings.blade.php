@extends('admin.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('AI Matchmaking Settings') }}</h1>
                <p class="mb-0 opacity-70">{{ translate('Control matching weights, recommendation limits, and AI feature availability from admin.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.v1_platform') }}" class="btn btn-soft-secondary">
                    <i class="las la-arrow-left mr-1"></i>{{ translate('AI Platform') }}
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.v1_platform.matchmaking_settings.update') }}" method="POST">
        @csrf

        <div class="row gutters-10">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">{{ translate('Engine Controls') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group row align-items-center">
                            <label class="col-md-7 col-from-label">{{ translate('Enable AI Matchmaking') }}</label>
                            <div class="col-md-5">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" name="ai_matchmaking_enabled" value="1"
                                        @if (get_setting('ai_matchmaking_enabled', $defaults['ai_matchmaking_enabled']) == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Minimum Match Score Shown') }}</label>
                            <input type="number" min="0" max="100" class="form-control"
                                name="ai_match_minimum_score"
                                value="{{ get_setting('ai_match_minimum_score', $defaults['ai_match_minimum_score']) }}">
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Daily Recommended Profiles Limit') }}</label>
                            <input type="number" min="1" max="100" class="form-control"
                                name="ai_match_daily_recommendation_limit"
                                value="{{ get_setting('ai_match_daily_recommendation_limit', $defaults['ai_match_daily_recommendation_limit']) }}">
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Minimum Personality Trait Overlap') }}</label>
                            <input type="number" min="1" max="10" class="form-control"
                                name="ai_match_trait_overlap_minimum"
                                value="{{ get_setting('ai_match_trait_overlap_minimum', $defaults['ai_match_trait_overlap_minimum']) }}">
                        </div>

                        <div class="form-group mb-0">
                            <label>{{ translate('Cold Start Minimum Profile Signals') }}</label>
                            <input type="number" min="1" max="20" class="form-control"
                                name="ai_match_cold_start_minimum_signals"
                                value="{{ get_setting('ai_match_cold_start_minimum_signals', $defaults['ai_match_cold_start_minimum_signals']) }}">
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>{{ translate('Online Now Window (Minutes)') }}</label>
                            <input type="number" min="1" max="1440" class="form-control"
                                name="search_online_window_minutes"
                                value="{{ get_setting('search_online_window_minutes', $defaults['search_online_window_minutes']) }}">
                        </div>

                        <div class="form-group mb-0">
                            <label>{{ translate('Proposal Expiry Days') }}</label>
                            <input type="number" min="1" max="365" class="form-control"
                                name="proposal_expiry_days"
                                value="{{ get_setting('proposal_expiry_days', $defaults['proposal_expiry_days']) }}">
                        </div>

                        <div class="form-group mt-3 mb-0">
                            <label>{{ translate('Recency Boost Points') }}</label>
                            <input type="number" min="0" max="20" class="form-control"
                                name="ai_match_recency_boost_points"
                                value="{{ get_setting('ai_match_recency_boost_points', $defaults['ai_match_recency_boost_points']) }}">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">{{ translate('Feature Toggles') }}</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($features as $key => $label)
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div>
                                    <div class="fw-600">{{ translate($label) }}</div>
                                    @if (in_array($key, ['ai_match_photo_attractiveness_enabled', 'ai_match_voice_tone_enabled', 'ai_match_context_aware_enabled', 'ai_match_dormant_reengagement_enabled'], true))
                                        <div class="fs-11 text-warning">{{ translate('Optional advanced feature. Keep disabled until real opt-in processing is connected.') }}</div>
                                    @endif
                                </div>
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" name="{{ $key }}" value="1"
                                        @if (get_setting($key, $defaults[$key] ?? 0) == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">{{ translate('Compatibility Weights') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ translate('Weights are relative. Set a feature weight to 0 to remove it from scoring without deleting the module.') }}
                        </div>

                        <div class="row gutters-10">
                            @foreach ($weights as $key => $label)
                                @php($settingKey = 'ai_match_weight_' . $key)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate($label) }}</label>
                                        <input type="number" min="0" max="100" class="form-control"
                                            name="{{ $settingKey }}"
                                            value="{{ get_setting($settingKey, $defaults[$settingKey] ?? 0) }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="las la-save mr-1"></i>{{ translate('Save Matchmaking Settings') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">{{ translate('Module 4 Implementation Status') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-raw">
                                    <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Weighted matching score') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Compatibility explanation') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Daily recommendations limit') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Cold-start profile signal handling') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('NLP-style bio trait extraction heuristic') }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-raw">
                                    <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Photo attractiveness requires opt-in AI vision provider') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Voice tone requires audio analysis provider') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Mood/time context requires client-side signal collection') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Post-marriage success prediction needs outcome data') }}</li>
                                    <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Dormant re-engagement needs scheduled behavioral jobs') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
