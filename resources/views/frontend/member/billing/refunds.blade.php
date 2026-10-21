@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="aiz-titlebar"><h1 class="h3">{{ translate('Payment & Refund Requests') }}</h1></div>
<p class="text-muted">{{ translate('Review failed or completed payments in Payment History. Refund requests are reviewed by the team; submitting a request does not reverse a charge.') }}</p>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">{{ translate('Request Refund Review') }}</h2></div><div class="card-body">
@if($payments->isEmpty())<p>{{ translate('You have no eligible charged payments.') }}</p>
@else <form method="post" action="{{ route('member.payment_refunds.store') }}">@csrf
<div class="form-group"><label for="refund-payment">{{ translate('Paid Invoice') }}</label><select class="form-control" id="refund-payment" name="payment_id" required>
@foreach($payments as $payment)<option value="{{ $payment->id }}">{{ $payment->invoice_number ?? $payment->payment_code }} — {{ $payment->payable_amount }} {{ $payment->currency ?: 'PKR' }}</option>@endforeach
</select></div>
<div class="form-group"><label for="refund-reason">{{ translate('Reason') }}</label><textarea class="form-control" name="reason" id="refund-reason" rows="4" maxlength="2000" minlength="10" required>{{ old('reason') }}</textarea></div>
<button class="btn btn-primary" type="submit">{{ translate('Submit Request for Review') }}</button></form>@endif
</div></div>
<div class="card"><div class="card-header"><h2 class="h6 mb-0">{{ translate('Request History') }}</h2></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>{{ translate('Invoice') }}</th><th>{{ translate('Amount') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Updated') }}</th></tr></thead><tbody>
@forelse($requests as $refund)<tr><td>#{{ $refund->package_payment_id }}</td><td>{{ $refund->amount }} {{ $refund->currency }}</td><td>{{ str_replace('_',' ',$refund->status) }}</td><td>{{ $refund->updated_at?->format('Y-m-d H:i') }}</td></tr>
@empty<tr><td colspan="4">{{ translate('No refund requests') }}</td></tr>@endforelse
</tbody></table></div><div class="aiz-pagination">{{ $requests->links() }}</div></div>
@endsection
