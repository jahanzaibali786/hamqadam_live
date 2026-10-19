<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Observed Retention by Signup Month') }}</h5></div>
    <div class="card-body">
        <p class="text-muted small">{{ translate('D1 / D7 / D30 mean recorded activity on that day after signup, not last login or inferred engagement. Empty windows are shown as N/A. Analytics events are required.') }}</p>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>{{ translate('Cohort') }}</th><th>{{ translate('Registrations') }}</th><th>D1</th><th>D7</th><th>D30</th></tr></thead>
                <tbody>
                @forelse(($report['observed_retention'] ?? []) as $row)
                    <tr><td>{{ $row['month'] }}</td><td>{{ $row['signups'] }}</td>
                    @foreach([1,7,30] as $day)
                        <td>{{ $row['d'.$day] === null ? 'N/A' : $row['d'.$day].'%' }} <small class="text-muted">({{ $row['d'.$day.'_eligible'] }} {{ translate('eligible') }})</small></td>
                    @endforeach
                    </tr>
                @empty
                    <tr><td colspan="5">{{ translate('Observed cohort report requires MySQL and analytics event history.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
