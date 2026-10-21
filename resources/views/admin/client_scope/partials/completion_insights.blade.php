@php($hqInsights = app(\App\Services\ClientScope\AnalyticsInsightsService::class)->summarize(30))
<section class="card mb-4" aria-labelledby="hq-analytics-heading">
  <div class="card-header"><h2 class="h5 mb-0" id="hq-analytics-heading">Verified event activity — last 30 days</h2></div>
  <div class="card-body">
    <p class="text-muted">Each metric reflects recorded events, not an inferred conversion funnel. Cohort populations and event membership can differ. Missing events mean unknown instrumentation coverage, not zero activity.</p>
    <div class="row mb-3">
      <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted">Members with events</div><strong class="h3">{{ number_format($hqInsights['activeUsers']) }}</strong></div></div>
      <div class="col-md-8"><div class="border rounded p-3"><div class="text-muted">Total event types observed</div><strong class="h3">{{ $hqInsights['eventRows']->count() }}</strong></div></div>
    </div>
    <h3 class="h6">Journey events</h3>
    <div class="table-responsive"><table class="table table-sm table-striped"><caption class="sr-only">Recorded journey events</caption><thead><tr><th scope="col">Stage</th><th scope="col">Observed members</th></tr></thead><tbody>
      @foreach($hqInsights['stages'] as $stage => $members)
        <tr><th scope="row">{{ ucwords(str_replace('_',' ',$stage)) }}</th><td>{{ number_format($members) }}</td></tr>
      @endforeach
    </tbody></table></div>
    <h3 class="h6 mt-4">Feature adoption</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th scope="col">Feature</th><th scope="col">Usage events</th><th scope="col">Unique members</th></tr></thead><tbody>
      @forelse($hqInsights['adoption'] as $feature)
        <tr><th scope="row">{{ $feature->feature_key }}</th><td>{{ number_format($feature->uses) }}</td><td>{{ number_format($feature->members) }}</td></tr>
      @empty <tr><td colspan="3">No instrumented feature activity yet.</td></tr> @endforelse
    </tbody></table></div>
    <p class="small text-muted" role="note">Only explicitly recorded member events are included. Anonymous activity and uninstrumented client screens are not counted; do not interpret missing data as zero activity.</p>
    <h3 class="h6 mt-4">Geographic member distribution (top cities)</h3>
    @php($maxCity = max(1,(int)($hqInsights['geography']->max('members') ?? 0)))
    <div class="row">
    @forelse($hqInsights['geography'] as $city)
      <div class="col-md-4 mb-2"><div class="p-2 border rounded">
        <div class="d-flex justify-content-between"><span>{{ $city->city ?? ('City #'.$city->city_id) }}</span><strong>{{ number_format($city->members) }}</strong></div>
        <div class="progress" role="meter" aria-label="Relative member count" aria-valuemin="0" aria-valuemax="{{ $maxCity }}" aria-valuenow="{{ $city->members }}">
          <div class="progress-bar" style="width:{{ round(100*$city->members/$maxCity) }}%"></div>
        </div>
      </div></div>
    @empty <p class="col-12">No location data available for geographic reporting.</p> @endforelse
    </div>
  </div>
</section>
