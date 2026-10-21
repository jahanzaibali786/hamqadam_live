<section class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Translation coverage audit</h2></div><div class="card-body">
<p class="small text-muted">Coverage is based on database translation keys. It does not certify linguistic accuracy, Blade strings, JavaScript, or the mobile app. Approved translations must be entered using the language administrator.</p>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Language</th><th>Nonempty keys</th><th>Reference keys</th><th>Coverage</th><th>Missing examples</th></tr></thead><tbody>
@forelse(($report['translation_coverage'] ?? []) as $row)
<tr><td>{{ strtoupper($row['language']) }}</td><td>{{ $row['translated'] }}</td><td>{{ $row['reference_keys'] }}</td><td>{{ $row['coverage_pct'] === null ? 'Unknown' : $row['coverage_pct'].'%' }}</td><td><small>{{ implode(', ',array_slice($row['sample_missing_keys'],0,5)) }}</small></td></tr>
@empty<tr><td colspan="5">Translation table unavailable</td></tr>@endforelse
</tbody></table></div></div></section>
