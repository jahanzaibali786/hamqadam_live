<section class="card mb-4" aria-labelledby="hq-outcome-title"><div class="card-header"><h2 class="h6 mb-0" id="hq-outcome-title">Verified match outcomes</h2></div><div class="card-body">
<div class="row"><div class="col-md-4"><strong>{{ number_format($report['outcome_candidates'] ?? 0) }}</strong> mutual-report review candidates</div>
<div class="col-md-4"><strong>{{ number_format($report['verified_outcomes'] ?? 0) }}</strong> staff-reviewed outcomes</div>
<div class="col-md-4"><strong>{{ number_format($report['verified_stories'] ?? 0) }}</strong> linked story records</div></div>
<p class="small text-muted mt-2">Staff-reviewed outcomes are not evidence of a legally documented marriage. Counts are based on a separate auditable review ledger.</p>
<a href="{{ route('admin.client.scope.outcomes.index') }}">Open outcome reviews</a></div></section>
<section class="card mb-4" aria-labelledby="hq-geo-title"><div class="card-header"><h2 class="h6 mb-0" id="hq-geo-title">Member geographic concentration (coordinates supplied by staff)</h2></div><div class="card-body">
@php($map = $report['geo_heatmap'] ?? ['pins'=>[],'mapped_members'=>0,'unmapped_members'=>0])
<p class="small text-muted">Aggregated counts only. No member address or private location is displayed. {{ $map['mapped_members'] }} mapped records (city aggregate) and {{ $map['unmapped_members'] }} with city but no verified coordinates. Only the top 250 city groups are considered.</p>
<div class="table-responsive"><svg viewBox="0 0 800 390" role="img" aria-label="Aggregate member distribution by city coordinates, plotted on a latitude longitude grid" style="width:100%;max-height:400px;background:#eef5f8;border:1px solid #d1dae0">
@for($lat=-60;$lat<=60;$lat+=30)<line x1="0" x2="800" y1="{{ 195-$lat*2.1666667 }}" y2="{{ 195-$lat*2.1666667 }}" stroke="#cad6de" stroke-width="1"/>@endfor
@for($lon=-150;$lon<=150;$lon+=30)<line y1="0" y2="390" x1="{{ ($lon+180)*800/360 }}" x2="{{ ($lon+180)*800/360 }}" stroke="#cad6de" stroke-width="1"/>@endfor
@foreach($map['pins'] as $pin)
@php($x=($pin['longitude']+180)*800/360)
@php($y=(90-$pin['latitude'])*390/180)
<circle cx="{{ $x }}" cy="{{ $y }}" r="{{ min(28,5+sqrt($pin['members'])*2) }}" fill="#0e7490" fill-opacity=".45" stroke="#065f76" stroke-width="1"><title>{{ $pin['city'] }}: {{ $pin['members'] }} profiles</title></circle>
@endforeach
</svg></div>
<p class="small text-muted">Chart is an equirectangular coordinate grid, not a political boundary map. Bubble size represents observed profiles with a saved city.</p>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>City</th><th>Profiles</th></tr></thead><tbody>@forelse($map['pins'] as $pin)<tr><td>{{ $pin['city'] }}</td><td>{{ $pin['members'] }}</td></tr>@empty<tr><td colspan="2">No city coordinates entered yet.</td></tr>@endforelse</tbody></table></div>
<h3 class="h6 mt-3">Set a city's approximate center coordinates</h3>
<form method="post" action="{{ route('admin.client.scope.city_coordinates.store') }}">@csrf
<div class="form-row"><div class="col-md-4"><label>City database ID</label><input class="form-control" type="number" name="city_id" min="1" required></div>
<div class="col-md-3"><label>Latitude (-90 to 90)</label><input class="form-control" type="number" step="any" name="latitude" min="-90" max="90" required></div>
<div class="col-md-3"><label>Longitude (-180 to 180)</label><input class="form-control" type="number" step="any" name="longitude" min="-180" max="180" required></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Save location</button></div></div></form>
</div></section>
