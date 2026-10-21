@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mb-3"><h1 class="h3">{{ translate('Payment Reconciliation Audit') }}</h1></div>
<p class="alert alert-info">Read-only audit. This page does not process payments, issue refunds, or change subscription balances.</p>
<div class="row mb-3">
@foreach(['paid'=>'Paid','due'=>'Due','failed'=>'Failed','cancelled'=>'Cancelled','stale_due'=>'Due over 24h','paid_without_paid_at'=>'Paid without timestamp','refunds_to_review'=>'Refund requests','unprocessed_webhook_events'=>'Unprocessed webhooks','duplicate_gateway_references'=>'Duplicate gateway refs'] as $key=>$label)
<div class="col-lg-3 col-md-4 col-sm-6 mb-2"><div class="card h-100"><div class="card-body"><span class="text-muted small">{{ $label }}</span><div class="h4 mb-0">{{ number_format($summary[$key] ?? 0) }}</div></div></div></div>
@endforeach
</div>
<form method="get" class="card card-body mb-3"><div class="row"><div class="col-md-3 mb-2"><label for="audit-status">Status</label><select class="form-control" name="status" id="audit-status"><option value="">All</option>@foreach(['Due','Paid','Failed','Cancelled'] as $status)<option @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select></div><div class="col-md-3 mb-2"><label for="audit-gateway">Gateway</label><select class="form-control" name="gateway" id="audit-gateway"><option value="">All</option>@foreach(['stripe','easypaisa','jazzcash'] as $gateway)<option value="{{ $gateway }}" @selected(request('gateway')===$gateway)>{{ ucfirst($gateway) }}</option>@endforeach</select></div><div class="col-md-4 mb-2"><label for="audit-reference">Gateway reference</label><input id="audit-reference" class="form-control" name="reference" maxlength="100" value="{{ request('reference') }}"></div><div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filter</button></div></div></form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Member</th><th>Gateway</th><th>Status</th><th>Amount</th><th>Reference</th><th>Paid at</th><th>Created</th></tr></thead><tbody>
@forelse($payments as $payment)
<tr><td>{{ $payment->id }}</td><td>{{ $payment->user?->email ?? '#'.$payment->user_id }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->payment_status }}</td><td>{{ number_format((float)($payment->payable_amount ?? $payment->amount), 2) }} {{ $payment->currency ?? 'PKR' }}</td><td><code>{{ $payment->gateway_reference }}</code></td><td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td><td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td></tr>
@empty
<tr><td colspan="8">No payments found</td></tr>
@endforelse
</tbody></table></div>{{ $payments->links() }}
@endsection
