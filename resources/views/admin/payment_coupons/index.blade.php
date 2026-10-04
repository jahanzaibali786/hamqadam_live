@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('Promo Codes') }}</h1>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Promo Code') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payment-coupons.store') }}">@csrf
                    @include('admin.payment_coupons.partials.fields', ['coupon' => null, 'prefix' => 'create'])
                    <button class="btn btn-primary btn-block">{{ translate('Create Promo Code') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Managed Promo Codes') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead><tr><th>{{ translate('Code') }}</th><th>{{ translate('Discount') }}</th><th>{{ translate('Usage') }}</th><th>{{ translate('Validity') }}</th><th>{{ translate('Status') }}</th><th class="text-right">{{ translate('Options') }}</th></tr></thead>
                        <tbody>
                        @forelse($coupons as $coupon)
                            <tr>
                                <td><strong>{{ $coupon->code }}</strong></td>
                                <td>{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : single_price($coupon->discount_value) }}</td>
                                <td>{{ $coupon->used_count }} / {{ $coupon->usage_limit ?: translate('Unlimited') }}</td>
                                <td>{{ optional($coupon->starts_at)->format('d M Y') ?: translate('Now') }} - {{ optional($coupon->expires_at)->format('d M Y') ?: translate('No expiry') }}</td>
                                <td><span class="badge badge-inline badge-{{ $coupon->active ? 'success' : 'secondary' }}">{{ $coupon->active ? translate('Active') : translate('Disabled') }}</span></td>
                                <td class="text-right">
                                    <button class="btn btn-soft-info btn-icon btn-circle btn-sm" data-toggle="modal" data-target="#coupon-edit-{{ $coupon->id }}"><i class="las la-edit"></i></button>
                                    <form method="POST" action="{{ route('payment-coupons.destroy', $coupon) }}" class="d-inline" onsubmit="return confirm('{{ translate('Delete or disable this promo code?') }}')">@csrf @method('DELETE')<button class="btn btn-soft-danger btn-icon btn-circle btn-sm"><i class="las la-trash"></i></button></form>
                                </td>
                            </tr>
                            <div class="modal fade" id="coupon-edit-{{ $coupon->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>{{ translate('Edit Promo Code') }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><form method="POST" action="{{ route('payment-coupons.update', $coupon) }}">@csrf @method('PUT')<div class="modal-body">@include('admin.payment_coupons.partials.fields', ['coupon' => $coupon, 'prefix' => 'edit_'.$coupon->id])</div><div class="modal-footer"><button class="btn btn-primary">{{ translate('Save Changes') }}</button></div></form></div></div></div>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('No promo codes created yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="aiz-pagination mt-3">{{ $coupons->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
