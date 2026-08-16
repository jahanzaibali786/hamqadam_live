@extends('frontend.layouts.member_panel')
@section('panel_content')
    @php
        $coinBalance = (int) (Auth::user()->member?->remaining_interest ?? 0);
    @endphp
    <div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
      <div class="col-md-6">
          <h1 class="h3">{{ translate('My Wallet') }}</h1>
      </div>
    </div>
    </div>
    <div class="row gutters-10">
      <div class="col-md-4 mx-auto mb-3" >
          <div class="bg-grad-1 text-white rounded-lg overflow-hidden">
            <span class="size-30px rounded-circle mx-auto bg-soft-primary d-flex align-items-center justify-content-center mt-3">
                <i class="las la-coins la-2x text-white"></i>
            </span>
            <div class="px-3 pt-3 pb-3">
                <div class="h4 fw-700 text-center">{{ $coinBalance }}</div>
                <div class="opacity-50 text-center">{{ translate('Current Coin Balance') }}</div>
            </div>
          </div>
      </div>
      <div class="col-md-4 mx-auto mb-3" >
        <a href="{{ route('packages') }}">
          <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition bg-soft-info">
              <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                  <i class="las la-plus la-3x text-white"></i>
              </span>
              <div class="fs-18 text-primary">{{ translate('Buy Package') }}</div>
          </div>
        </a>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
          <h5 class="mb-0 h6">{{ translate('Package Purchase History')}}</h5>
      </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                  <tr>
                      <th>#</th>
                      <th data-breakpoints="lg">{{ translate('Invoice Code') }}</th>
                      <th>{{ translate('Package') }}</th>
                      <th data-breakpoints="lg">{{ translate('Payment Method') }}</th>
                      <th>{{ translate('Amount') }}</th>
                      <th data-breakpoints="lg">{{ translate('Status') }}</th>
                      <th data-breakpoints="lg">{{ translate('Date') }}</th>
                      <th class="text-right">{{ translate('Invoice') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($package_payments as $key => $package_payment)
                      <tr>
                          <td>{{ ($key+1) + ($package_payments->currentPage() - 1) * $package_payments->perPage() }}</td>
                          <td>{{ $package_payment->payment_code }}</td>
                          <td>{{ $package_payment->package?->name ?? translate('Package') }}</td>
                          <td>
                              @if($package_payment->payment_method == "manual_payment")
                                  {{ $package_payment->custom_payment_name }}
                              @elseif(in_array($package_payment->payment_method, ['easypaisa', 'jazzcash']))
                                  {{ $package_payment->custom_payment_name ?: ucfirst($package_payment->payment_method) }}
                              @else
                                  {{ ucwords($package_payment->payment_method) }}
                              @endif
                          </td>
                          <td>{{ single_price($package_payment->amount) }}</td>
                          <td>
                              @if ($package_payment->payment_status == 'Paid')
                                  <span class="badge badge-inline badge-success">{{ translate('Paid') }}</span>
                              @elseif ($package_payment->payment_status == 'Due')
                                  <span class="badge badge-inline badge-warning">{{ translate('Pending Approval') }}</span>
                              @else
                                  <span class="badge badge-inline badge-danger">{{ translate('Unpaid') }}</span>
                              @endif
                          </td>
                          <td>{{ date('d-m-Y', strtotime($package_payment->created_at)) }}</td>
                          <td class="text-right">
                              <a href="{{ route('package_payment.invoice', $package_payment->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('Invoice') }}">
                                  <i class="las la-file-invoice"></i>
                              </a>
                          </td>
                      </tr>
                  @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $package_payments->links() }}
            </div>
        </div>
    </div>
@endsection
