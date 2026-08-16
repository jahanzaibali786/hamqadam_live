@extends('admin.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('AI Platform Control Center') }}</h1>
                <p class="mb-0 opacity-70">{{ translate('Operational view for the API-first matrimonial modules.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('api.docs') }}" class="btn btn-soft-primary" target="_blank">
                    <i class="las la-book mr-1"></i>{{ translate('API Docs') }}
                </a>
                <a href="{{ route('admin.v1_platform.matchmaking_settings') }}" class="btn btn-primary">
                    <i class="las la-sliders-h mr-1"></i>{{ translate('Matchmaking Settings') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-10">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="opacity-60">{{ translate('Total Members') }}</div>
                    <div class="h3 fw-700 mb-1">{{ $members['total'] }}</div>
                    <div class="fs-12">{{ $members['pending'] }} {{ translate('pending approval') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="opacity-60">{{ translate('Moderation Queue') }}</div>
                    <div class="h3 fw-700 mb-1">{{ $queues['moderation'] }}</div>
                    <div class="fs-12">{{ $members['blocked'] }} {{ translate('blocked members') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="opacity-60">{{ translate('Verification Queue') }}</div>
                    <div class="h3 fw-700 mb-1">{{ $queues['verification'] }}</div>
                    <div class="fs-12">{{ $queues['family'] }} {{ translate('family approvals pending') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="opacity-60">{{ translate('AI Requests Today') }}</div>
                    <div class="h3 fw-700 mb-1">{{ $ai['today'] }}</div>
                    <div class="fs-12">{{ $ai['pending'] }} {{ translate('waiting for queue processing') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-10">
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">{{ translate('Payments') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Paid invoices') }}</span>
                        <strong>{{ $payments['paid'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ translate('Due invoices') }}</span>
                        <strong>{{ $payments['due'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ translate('Paid revenue') }}</span>
                        <strong>{{ single_price($payments['revenue']) }}</strong>
                    </div>
                    <a href="{{ route('package-payments.index') }}" class="btn btn-sm btn-soft-primary mt-3">
                        {{ translate('Open Payment History') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">{{ translate('API-First Rollout Checklist') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row gutters-10">
                        <div class="col-md-6">
                            <ul class="list-group list-group-raw">
                                <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Versioned REST API') }}: <code>/api/v1</code></li>
                                <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('Sanctum authentication and device sessions') }}</li>
                                <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('AI matching, search, proposals, chat, safety') }}</li>
                                <li class="list-group-item px-0"><i class="las la-check text-success mr-2"></i>{{ translate('OpenAPI and Postman collections') }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-raw">
                                <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Run framework jobs/cache migrations before queue workers') }}</li>
                                <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Configure OpenAI, Stripe, EasyPaisa, FCM credentials') }}</li>
                                <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Enable Horizon/Reverb in production') }}</li>
                                <li class="list-group-item px-0"><i class="las la-exclamation-circle text-warning mr-2"></i>{{ translate('Fix PHPUnit dependency mismatch before CI') }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ url('/api/openapi-v1.json') }}" class="btn btn-sm btn-outline-secondary" target="_blank">OpenAPI JSON</a>
                        <a href="{{ url('/api/postman-v1.json') }}" class="btn btn-sm btn-outline-secondary" target="_blank">Postman JSON</a>
                        <a href="{{ url('/api/v1/admin/overview') }}" class="btn btn-sm btn-outline-secondary" target="_blank">Admin Overview API</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-10">
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0">{{ translate('Recent Verifications') }}</h6></div>
                <div class="card-body">
                    @forelse($recentVerifications as $verification)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>{{ optional($verification->user)->first_name }} {{ optional($verification->user)->last_name }}</strong>
                            <div class="fs-12 opacity-70">{{ $verification->status instanceof \BackedEnum ? $verification->status->value : $verification->status }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ translate('No verification requests yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0">{{ translate('Recent Moderation Cases') }}</h6></div>
                <div class="card-body">
                    @forelse($recentModerationCases as $case)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>{{ ucfirst((string) $case->case_type) }}</strong>
                            <div class="fs-12 opacity-70">{{ $case->reason }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ translate('No moderation cases yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0">{{ translate('Recent AI Requests') }}</h6></div>
                <div class="card-body">
                    @forelse($recentAiRequests as $request)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>{{ str_replace('_', ' ', ucfirst((string) $request->feature)) }}</strong>
                            <div class="fs-12 opacity-70">{{ $request->status }} · {{ $request->created_at?->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ translate('No AI requests yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
