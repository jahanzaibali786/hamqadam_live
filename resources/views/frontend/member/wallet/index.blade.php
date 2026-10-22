@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
    $coinBalance=(int)(auth()->user()->member?->remaining_interest ?? 0);
    $views=(int)(auth()->user()->member?->remaining_profile_viewer_view ?? 0);
    $plan=auth()->user()->member?->package?->name ?? translate('Free');
@endphp
<div class="hm-ref-page hm-wallet-reference" data-hm-screen="wallet">
  <header class="hm-ref-header"><div><div class="hm-ref-kicker">Your membership · finance</div><h1 class="hm-ref-heading"><i class="las la-wallet text-primary mr-2"></i>My Wallet</h1><p class="hm-ref-lead">Manage coin balance, subscriptions and transaction records.</p></div><a class="hm-ref-button secondary" href="{{ route('packages') }}"><i class="las la-gem"></i> View Plans</a></header>
  <div class="hm-ref-stats">
    <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-coins"></i></span><div><small>Current Coin Balance</small><strong>{{ number_format($coinBalance) }}</strong></div></div>
    <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-eye"></i></span><div><small>Profile View Balance</small><strong>{{ number_format($views) }}</strong></div></div>
    <div class="hm-ref-stat"><span class="hm-ref-stat-icon"><i class="las la-gem"></i></span><div><small>Current Package</small><strong style="font-size:19px">{{ $plan }}</strong></div></div>
    <a href="{{ route('custom_coins.form') }}" class="hm-ref-stat text-decoration-none"><span class="hm-ref-stat-icon"><i class="las la-cart-plus"></i></span><div><small>Buy Custom Coins</small><strong style="font-size:19px;color:#e72e77">Add Coins →</strong></div></a>
  </div>
  <div class="hm-ref-two">
    <div>
      <div class="hm-ref-wallet-banner"><div><small>AVAILABLE BALANCE</small><strong>{{ number_format($coinBalance) }} Coins</strong><p>Connect responsibly with compatible members.</p><a href="{{ route('custom_coins.form') }}" class="hm-ref-button secondary"><i class="las la-plus"></i> Buy Coins</a></div><div class="hm-ref-round"><i class="las la-coins"></i></div></div>
      <section class="hm-ref-panel">
        <div class="hm-ref-panel-head"><h2><i class="las la-gift text-primary mr-2"></i>Get More Coins</h2><span class="text-muted small">Choose a coin amount at checkout</span></div>
        <div class="hm-ref-panel-body"><div class="hm-ref-coin-choices">
          @foreach([50,100,250,500] as $amount)
          <a class="hm-ref-coin-option" href="{{ route('custom_coins.form') }}"><i class="las la-coins"></i><strong>{{ $amount }} Coins</strong><span>Choose amount and pay securely</span><b>Buy Coins <i class="las la-arrow-right"></i></b></a>
          @endforeach
        </div></div>
      </section>
      <section class="hm-ref-panel">
        <div class="hm-ref-panel-head"><h2>Transaction History</h2><span class="text-muted small">Invoices and payment statuses</span></div>
        <div class="hm-ref-panel-body hm-ref-history">
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

        </div>
      </section>
    </div>
    <aside>
      <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2>Quick Actions</h2></div><div class="hm-ref-panel-body hm-ref-side-links">
        <a href="{{ route('custom_coins.form') }}"><i class="las la-cart-plus"></i> Buy Coins</a>
        <a href="{{ route('packages') }}"><i class="las la-gem"></i> Upgrade Package</a>
        <a href="{{ route('package_purchase_history') }}"><i class="las la-receipt"></i> Payment History</a>
        <a href="{{ route('package_usage_history') }}"><i class="las la-history"></i> Coin Usage History</a>
      </div></section>
      <section class="hm-ref-panel"><div class="hm-ref-panel-head"><h2>Your Privacy Matters</h2></div><div class="hm-ref-panel-body"><p class="mb-2">Your payment information is handled through the configured payment provider.</p><p class="small text-muted mb-0">Payment totals and statuses displayed above come from actual transaction records.</p></div></section>
    </aside>
  </div>
</div>
@endsection
