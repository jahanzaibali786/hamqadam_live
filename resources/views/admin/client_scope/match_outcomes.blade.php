@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-4"><h1 class="h3">Independent Match Outcome Review</h1></div>
<p class="text-muted">A mutual Got Match report is not a verified marriage. An authorized staff review is recorded separately and audited.</p>
<form method="GET" class="mb-3"><label for="outcome-status">Filter status</label><select name="status" id="outcome-status"><option value="">All</option>@foreach(['pending','verified','rejected'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="btn btn-sm btn-primary">Filter</button></form>
@forelse($items as $item)
<div class="card mb-3"><div class="card-body">
<strong>Pair #{{ $item->member_a_id }} / #{{ $item->member_b_id }}</strong>
<span class="badge badge-info">{{ $item->status }}</span>
<p class="small text-muted">Reviewed by: {{ $item->reviewed_by ?? 'Not reviewed' }} · {{ $item->reviewed_at ?? 'Pending' }}</p>
<form method="POST" action="{{ route('admin.client.scope.outcomes.review',$item) }}">@csrf
<div class="form-row"><div class="col-md-2"><label>Status</label><select class="form-control" name="status"><option value="verified">Verified</option><option value="rejected">Rejected</option></select></div>
<div class="col-md-3"><label>Method</label><select class="form-control" name="verification_method"><option value="staff_contact">Staff contact</option><option value="documents">Documents</option><option value="other">Other</option></select></div>
<div class="col-md-3"><label>Related story ID (optional)</label><input type="number" min="1" name="happy_story_id" class="form-control" value="{{ $item->happy_story_id }}"></div>
<div class="col-md-4"><label>Internal notes</label><input class="form-control" name="internal_notes" maxlength="2000"></div></div>
<button class="btn btn-primary mt-3">Record staff review</button></form></div></div>
@empty <p>No candidate match outcomes have been recorded yet.</p>@endforelse
{{ $items->links() }}
@endsection
