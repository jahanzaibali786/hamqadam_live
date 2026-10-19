@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mb-3"><h1 class="h3">{{ translate('Payment Refund Requests') }}</h1></div>
<p class="alert alert-warning">{{ translate('Tracking only: this admin screen never sends money to a payment gateway. Confirm provider refunds independently before marking confirmed.') }}</p>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
<form method="get" class="mb-3"><label>{{ translate('Filter status') }} <select name="status" class="form-control"><option value="">{{ translate('All') }}</option>@foreach(['requested','reviewing','rejected','provider_refund_confirmed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></label><button class="btn btn-primary ml-2">{{ translate('Filter') }}</button></form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>{{ translate('Member') }}</th><th>{{ translate('Payment') }}</th><th>{{ translate('Amount') }}</th><th>{{ translate('Reason') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Review') }}</th></tr></thead><tbody>
@forelse($items as $refund)
<tr><td>{{ $refund->id }}</td><td>{{ $refund->user?->email ?? $refund->user_id }}</td><td>#{{ $refund->package_payment_id }}</td><td>{{ $refund->amount }} {{ $refund->currency }}</td><td>{{ $refund->reason }}</td><td>{{ $refund->status }}</td><td>
@if($refund->status!=='provider_refund_confirmed')
@if(config('hamqadam_payments.stripe_refunds_enabled', false) && $refund->payment?->payment_method === 'stripe' && in_array($refund->status, ['requested','reviewing'], true))
<form action="{{ route('admin.payment_refunds.execute', $refund) }}" method="POST" class="mb-2" onsubmit="return confirm('Issue or retry this Stripe refund? This moves real funds.');">@csrf
<label class="small"><input type="checkbox" name="confirm" value="1" required> I authorize refund of {{ $refund->amount }} {{ $refund->currency }}</label>
<button class="btn btn-danger btn-sm" type="submit">Issue verified Stripe refund</button></form>
@endif
<form action="{{ route('admin.payment_refunds.review',$refund) }}" method="POST">@csrf
<select name="status" class="form-control form-control-sm mb-1" required><option value="reviewing">{{ translate('Reviewing') }}</option><option value="rejected">{{ translate('Reject') }}</option><option value="provider_refund_confirmed">{{ translate('Provider-confirmed refund') }}</option></select>
<input name="provider_refund_reference" class="form-control form-control-sm mb-1" placeholder="{{ translate('Provider refund reference (mandatory if confirmed)') }}">
<textarea name="admin_note" class="form-control form-control-sm mb-1" rows="2" maxlength="2000" placeholder="{{ translate('Internal review note') }}"></textarea>
<label class="small"><input type="checkbox" name="provider_verified" value="1"> {{ translate('I have independently verified the provider refund') }}</label>
<button class="btn btn-sm btn-primary" type="submit">{{ translate('Save review') }}</button></form>
@else <span class="text-success">{{ translate('Provider confirmation recorded') }}: {{ $refund->provider_refund_reference }}</span> @endif
</td></tr>
@empty <tr><td colspan="7">{{ translate('No refund review requests') }}</td></tr>@endforelse
</tbody></table></div><div class="aiz-pagination">{{ $items->links() }}</div>
@endsection
