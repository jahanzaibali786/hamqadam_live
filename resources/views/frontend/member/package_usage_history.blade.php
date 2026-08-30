@extends('frontend.layouts.member_panel')
@section('panel_content')
    @php
        $visibleUsages = $usages->take(5);
        $hiddenUsages = $usages->slice(5);
    @endphp
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Package Usage History') }}</h1>
            </div>
        </div>
    </div>

    <div class="row gutters-5 mb-4">
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Total Coins') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ $totalPurchasedCoins }}</div>
                <div class="fs-12 opacity-70">{{ translate('Coins granted from purchased packages') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Total Usage') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ $totalUsedCoins }}</div>
                <div class="fs-12 opacity-70">{{ translate('Coins consumed across activities') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Profile Views Used') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ $profileViewsUsed }}</div>
                <div class="fs-12 opacity-70">{{ translate('Profile details opened with package allowance') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="text-muted fs-12 mb-1">{{ translate('Remaining Coins') }}</div>
                <div class="h3 fw-700 text-primary-grad mb-1">{{ $remainingCoins }}</div>
                <div class="fs-12 opacity-70">{{ translate('Current active entitlement balance') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Usage Activity Wall') }}</h5>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Feature') }}</th>
                    <th>{{ translate('Activity') }}</th>
                    <th>{{ translate('Used') }}</th>
                    <th>{{ translate('Date') }}</th>
                </tr>
                </thead>
                <tbody>
                @php $serial = 1; @endphp
                @foreach ($usages as $usage)
                    @if ($serial <= 5)
                        <tr>
                            <td>{{ $serial }}</td>
                            <td>{{ $usage->feature_label }}</td>
                            <td>{{ $usage->note ?: translate('Package entitlement used') }}</td>
                            <td>{{ (int) $usage->amount }} {{ translate('coin(s)') }}</td>
                            <td>{{ date('d-m-Y H:i', strtotime($usage->created_at)) }}</td>
                        </tr>
                    @else
                        <tr class="usage-hidden-row d-none" data-usage-row="more">
                            <td>{{ $serial }}</td>
                            <td>{{ $usage->feature_label }}</td>
                            <td>{{ $usage->note ?: translate('Package entitlement used') }}</td>
                            <td>{{ (int) $usage->amount }} {{ translate('coin(s)') }}</td>
                            <td>{{ date('d-m-Y H:i', strtotime($usage->created_at)) }}</td>
                        </tr>
                    @endif
                    @php $serial++; @endphp
                @endforeach

                @if ($usages->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center">{{ translate('No usage activity found yet.') }}</td>
                    </tr>
                @endif
                </tbody>
            </table>

            @if ($hiddenUsages->isNotEmpty())
                <div class="text-center mt-3">
                    <button type="button" id="loadMorePackageUsage" class="btn btn-primary btn-sm">{{ translate('Load More') }}</button>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var loadMoreButton = document.getElementById('loadMorePackageUsage');
            if (!loadMoreButton) {
                return;
            }

            loadMoreButton.addEventListener('click', function () {
                var hiddenRows = document.querySelectorAll('[data-usage-row="more"]');
                var visibleChunk = 5;

                for (var i = 0; i < hiddenRows.length && i < visibleChunk; i++) {
                    hiddenRows[i].classList.remove('d-none');
                }

                var nowHidden = document.querySelectorAll('[data-usage-row="more"].d-none');
                if (nowHidden.length === 0) {
                    loadMoreButton.style.display = 'none';
                }
            });
        });
    </script>
@endsection
