@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
  $user = auth()->user();
  $member = $user->member;
  $coins = max(0, (int)($member?->remaining_interest ?? 0));
  $views = max(0, (int)($member?->remaining_profile_viewer_view ?? 0));
  $package = $member?->package;
  $unitPrice = \App\Services\Api\V1\Payment\CustomCoinService::amountFor(1);
  $coinChoices = [50,100,250,500];
  $recentPayments = $package_payments->take(6);
@endphp
<div class="hm-2026 hm-wallet-2026">
  <header class="hm-2026-banner">
    <div class="hm-2026-heading"><span class="hm-2026-heading-icon"><i class="las la-wallet"></i></span><div><h1>My Wallet</h1><p>Manage your coins, payments and transactions.</p></div></div>
    <div class="hm-2026-banner-note"><i class="las la-coins"></i><strong>Use coins to show interest, unlock profiles and connect with compatible matches.</strong></div>
  </header>
  <div class="hm-2026-layout">
    <div class="hm-2026-main">
      <div class="hm-wallet-stats">
        <article class="hm-wallet-stat"><i class="las la-coins"></i><span>Current Coin Balance</span><strong>{{ number_format($coins) }} Coins</strong><small>Coins available in your account</small><a class="hm-pink-button" href="{{ route('custom_coins.form') }}">Buy Coins <i class="las la-arrow-right"></i></a></article>
        <article class="hm-wallet-stat"><i class="las la-eye"></i><span>Profile View Balance</span><strong>{{ number_format($views) }} Views</strong><small>Remaining profile-detail views</small><a class="hm-pink-button" href="{{ route('packages') }}">Upgrade Plan <i class="las la-arrow-right"></i></a></article>
        <article class="hm-wallet-stat"><i class="las la-gem"></i><span>Current Package</span><strong>{{ $package?->name ?? 'Free' }}</strong><small>Expires: {{ $member?->package_validity ? \Carbon\Carbon::parse($member->package_validity)->format('d M Y') : 'Not available' }}</small><a class="hm-pink-button" href="{{ route('packages') }}">View Plans <i class="las la-arrow-right"></i></a></article>
      </div>
      <section class="hm-wallet-panel" id="hm-wallet-purchase">
        <nav class="hm-wallet-tabs" aria-label="Wallet sections">
          <button type="button" class="is-active" data-wallet-tab="buy">Buy Coins</button>
          <button type="button" data-wallet-tab="history">Payment History</button>
          <button type="button" data-wallet-tab="usage">Coin Usage History</button>
        </nav>
        <div class="hm-wallet-tab-content" data-wallet-panel="buy">
          <div class="hm-wallet-choices">
            @foreach($coinChoices as $amount)
            <article class="hm-wallet-choice {{ $amount===100?'is-popular':'' }}">
              @if($amount===100)<span class="hm-wallet-popular">Popular Choice</span>@endif
              <i class="las la-coins"></i><strong>{{ $amount }} Coins</strong>
              <b>{{ single_price(\App\Services\Api\V1\Payment\CustomCoinService::amountFor($amount)) }}</b>
              <button class="hm-wallet-buy {{ $amount===100?'primary':'' }}" type="button" data-wallet-coins="{{ $amount }}">Buy Now</button>
            </article>
            @endforeach
          </div>
          <div class="hm-wallet-promo"><i class="las la-percent"></i><div><strong>Have a Promo Code?</strong><p>Promo discounts are applied only where supported by checkout.</p></div><a href="{{ route('custom_coins.form') }}">Go to Checkout <i class="las la-arrow-right"></i></a></div>
          <div class="hm-wallet-payments"><h2>Payment Methods</h2><p>Choose your secure and convenient payment method at checkout.</p><div class="hm-wallet-methods"><span><i class="las la-mobile-alt"></i> JazzCash</span><span><i class="las la-mobile"></i> Easypaisa</span><span><i class="las la-credit-card"></i> Debit / Credit Card</span><span><i class="las la-university"></i> Bank Transfer</span></div><small>Methods are available only when enabled by the administrator. Final selection and payment take place at the existing secure checkout.</small><a href="{{ route('custom_coins.form') }}" class="hm-pink-button wide"><i class="las la-lock"></i> Proceed to Payment <i class="las la-arrow-right"></i></a></div>
        </div>
        <div class="hm-wallet-tab-content" data-wallet-panel="history" hidden>
          <div class="hm-wallet-table"><table class="table"><thead><tr><th>Invoice</th><th>Purchase</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Date</th><th>Receipt</th></tr></thead><tbody>
          @forelse($package_payments as $payment)
          @php $coinPayload=\App\Services\Api\V1\Payment\CustomCoinService::payloadOf($payment); @endphp
          <tr><td>{{ $payment->payment_code }}</td><td>{{ $coinPayload ? $coinPayload['coins'].' Coins' : ($payment->package?->name ?? 'Package') }}</td><td>{{ single_price($payment->amount) }}</td><td>{{ $payment->custom_payment_name ?: ucwords(str_replace('_',' ',(string)$payment->payment_method)) }}</td><td><span class="hm-wallet-status">{{ $payment->payment_status }}</span></td><td>{{ $payment->created_at?->format('d M Y') }}</td><td><a href="{{ route('package_payment.invoice',$payment->id) }}">Invoice</a></td></tr>
          @empty<tr><td colspan="7">No payments yet.</td></tr>@endforelse
          </tbody></table></div>{{ $package_payments->links() }}
        </div>
        <div class="hm-wallet-tab-content" data-wallet-panel="usage" hidden><div class="hm-wallet-empty"><i class="las la-history"></i><h2>Coin Usage History</h2><p>Review your actual coin usage and package activity.</p><a class="hm-pink-button" href="{{ route('package_usage_history') }}">Open Usage History <i class="las la-arrow-right"></i></a></div></div>
      </section>
    </div>
    <aside class="hm-2026-aside">
      <section class="hm-wallet-aside-card"><h2>Quick Actions</h2><a href="{{ route('custom_coins.form') }}"><i class="las la-shopping-cart"></i>Buy Coins</a><a href="{{ route('package_purchase_history') }}"><i class="las la-file-invoice"></i>View Payment History</a><a href="{{ route('package_usage_history') }}"><i class="las la-sync"></i>Coin Usage History</a><a href="{{ route('packages') }}"><i class="las la-gem"></i>Upgrade Package</a></section>
      <section class="hm-wallet-aside-card"><h2>Recent Transactions</h2>
      @forelse($recentPayments as $payment)
        @php $recentCoins=\App\Services\Api\V1\Payment\CustomCoinService::payloadOf($payment); @endphp
        <a class="hm-wallet-transaction" href="{{ route('package_payment.invoice',$payment->id) }}"><span><i class="las {{ $payment->payment_status==='Paid'?'la-check-circle':'la-clock' }}"></i></span><span><strong>{{ $recentCoins ? $recentCoins['coins'].' Coins' : ($payment->package?->name ?? 'Package Purchase') }}</strong><small>{{ $payment->created_at?->format('d M Y · h:i A') }}</small></span><span><b>{{ single_price($payment->amount) }}</b><small>{{ $payment->payment_status }}</small></span></a>
      @empty<p class="hm-wallet-empty-text">No transactions yet.</p>@endforelse
      <a class="hm-wallet-viewall" href="{{ route('package_purchase_history') }}">View All Transactions <i class="las la-arrow-right"></i></a>
      </section>
    </aside>
  </div>
</div>
@endsection
@section('script')
<script>
(function(){
 const root=document.querySelector('.hm-wallet-2026'); if(!root)return;
 const tabs=root.querySelectorAll('[data-wallet-tab]'),panels=root.querySelectorAll('[data-wallet-panel]');
 function select(name){tabs.forEach(x=>{const a=x.dataset.walletTab===name;x.classList.toggle('is-active',a);x.setAttribute('aria-selected',String(a));});panels.forEach(x=>x.hidden=x.dataset.walletPanel!==name);}
 tabs.forEach(x=>x.addEventListener('click',()=>select(x.dataset.walletTab)));
 root.querySelectorAll('[data-wallet-coins]').forEach(button=>button.addEventListener('click',()=>{
  const url=new URL(@json(route('custom_coins.form')),location.origin);
  url.searchParams.set('coins',button.dataset.walletCoins);
  location.assign(url.toString());
 }));
 if(location.hash==='#history')select('history');
})();
</script>
@endsection