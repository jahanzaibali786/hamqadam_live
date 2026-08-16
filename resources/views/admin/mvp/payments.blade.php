@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('MVP Payment Approvals') }}</h1>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-0 h6">{{ translate('EasyPaisa / JazzCash / Manual Payments') }}</h5>
        </div>
        <div class="col-md-6">
            <form class="row gutters-5">
                <div class="col">
                    <select class="form-control aiz-selectpicker" name="gateway">
                        <option value="">{{ translate('All Gateways') }}</option>
                        @foreach (['easypaisa' => 'EasyPaisa', 'jazzcash' => 'JazzCash', 'manual_payment' => 'Manual'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('gateway') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <select class="form-control aiz-selectpicker" name="status">
                        <option value="">{{ translate('All Statuses') }}</option>
                        @foreach (['Due', 'Paid', 'Failed', 'Cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ translate($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">{{ translate('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Member') }}</th>
                    <th>{{ translate('Package') }}</th>
                    <th>{{ translate('Gateway') }}</th>
                    <th>{{ translate('Payable') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Reference') }}</th>
                    <th class="text-right">{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $key => $payment)
                    <tr>
                        <td>{{ ($key + 1) + ($payments->currentPage() - 1) * $payments->perPage() }}</td>
                        <td>{{ $payment->user?->first_name }} {{ $payment->user?->last_name }}</td>
                        <td>{{ $payment->package?->name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>{{ single_price($payment->payable_amount ?: $payment->amount) }}</td>
                        <td>
                            <span class="badge badge-inline badge-{{ $payment->payment_status === 'Paid' ? 'success' : ($payment->payment_status === 'Failed' ? 'danger' : 'warning') }}">
                                {{ translate($payment->payment_status) }}
                            </span>
                        </td>
                        <td class="text-break">{{ $payment->gateway_reference ?: $payment->payment_code }}</td>
                        <td class="text-right">
                            @if ($payment->payment_status !== 'Paid')
                                <a href="{{ route('manual_payment_accept', $payment->id) }}" class="btn btn-soft-success btn-icon btn-circle btn-sm" title="{{ translate('Approve') }}">
                                    <i class="las la-check"></i>
                                </a>
                                <form action="{{ route('admin.mvp.payments.reject', $payment->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-soft-danger btn-icon btn-circle btn-sm" title="{{ translate('Reject') }}">
                                        <i class="las la-times"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('package_payment.invoice_admin', $payment->id) }}" target="_blank" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Invoice') }}">
                                <i class="las la-file-invoice"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
