@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="container-fluid py-3">
    <h1 class="h3 mb-3">{{ translate('Refer Friends & Earn Coins') }}</h1>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="row mb-3">
        <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ translate('Referred members') }}</div><div class="h2">{{ $summary['total_referrals'] }}</div></div></div></div>
        <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ translate('Rewarded referrals') }}</div><div class="h2">{{ $summary['rewarded_referrals'] }}</div></div></div></div>
        <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ translate('Coins earned') }}</div><div class="h2">{{ $summary['coins_earned'] }}</div></div></div></div>
    </div>
    <div class="card mb-4"><div class="card-body">
        <h2 class="h5">{{ translate('Invite with your referral link') }}</h2>
        <p>{{ translate('Earn coins when a referred member completes registration, verifies their email and meets the reward eligibility rules.') }}</p>
        <label for="referral-code">{{ translate('Your referral code') }}</label>
        <input id="referral-code" class="form-control mb-2" readonly value="{{ $summary['referral_code'] }}">
        <label for="referral-share-url">{{ translate('Your referral link') }}</label>
        <div class="input-group"><input id="referral-share-url" class="form-control" readonly value="{{ $summary['referral_url'] }}"><div class="input-group-append"><button class="btn btn-primary" type="button" id="copy-referral-url">{{ translate('Copy') }}</button></div></div>
        <span id="copy-referral-message" class="small text-muted" role="status"></span>
        @if(!$summary['reward_rule']['active'])
            <div class="alert alert-info mt-3">{{ translate('Referral coin rewards are not enabled by the administrator yet. Your referrals are still tracked.') }}</div>
        @else
            <p class="mt-3 mb-0">{{ $summary['reward_rule']['coins_per_referral'] }} {{ translate('coins per eligible referral') }}. {{ translate('Maximum rewarded referrals') }}: {{ $summary['reward_rule']['max_rewards_per_member'] }}.</p>
        @endif
    </div></div>
    <div class="card mb-4"><div class="card-body">
        <h2 class="h5">{{ translate('Referred members') }}</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>{{ translate('Member') }}</th><th>{{ translate('Joined') }}</th><th>{{ translate('Email verification') }}</th><th>{{ translate('Reward') }}</th></tr></thead><tbody>
        @forelse($referrals as $person)
            @php($paid = in_array((int)$person->id, $rewardedIds, true))
            <tr><td>{{ $person->first_name }} {{ $person->last_name }}</td><td>{{ $person->created_at?->format('d M Y') }}</td><td>{{ $person->email_verified_at ? translate('Verified') : translate('Pending') }}</td><td>{{ $paid ? translate('Credited') : translate('Pending eligibility') }}</td></tr>
        @empty <tr><td colspan="4">{{ translate('No referrals yet.') }}</td></tr>
        @endforelse
        </tbody></table></div>{{ $referrals->links() }}
    </div></div>
    <div class="card mb-4"><div class="card-body">
        <h2 class="h5">{{ translate('Referral coin reward history') }}</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>{{ translate('Date') }}</th><th>{{ translate('Reward') }}</th><th>{{ translate('Reference') }}</th></tr></thead><tbody>
        @forelse($history as $reward)
            <tr><td>{{ $reward->created_at?->format('d M Y H:i') }}</td><td>+{{ $reward->coins }} {{ translate('coins') }}</td><td>#{{ $reward->reference_id }}</td></tr>
        @empty <tr><td colspan="3">{{ translate('No rewards credited yet.') }}</td></tr>
        @endforelse
        </tbody></table></div>{{ $history->links() }}
    </div></div>
    @if($commissions->isNotEmpty())
      <div class="card"><div class="card-body"><h2 class="h5">{{ translate('Legacy wallet referral commissions') }}</h2><p class="small text-muted">{{ translate('Wallet commissions are shown separately from coin rewards.') }}</p><div class="table-responsive"><table class="table"><tbody>
        @foreach($commissions as $commission)<tr><td>{{ $commission->created_at?->format('d M Y') }}</td><td>{{ $commission->amount ?? $commission->balance ?? '-' }}</td></tr>@endforeach
      </tbody></table></div></div></div>
    @endif
</div>
@endsection
@section('script')
<script>
document.getElementById('copy-referral-url')?.addEventListener('click', function () {
    const input = document.getElementById('referral-share-url');
    const message = document.getElementById('copy-referral-message');
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(() => { message.textContent = 'Link copied.'; }, () => { input.select(); message.textContent = 'Select and copy the link.'; });
    } else { input.select(); message.textContent = 'Select and copy the link.'; }
});
</script>
@endsection
