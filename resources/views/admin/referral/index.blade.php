@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-3 mb-3"><h1 class="h3">{{ translate('Referral Program & Coin Rewards') }}</h1></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row">
  <div class="col-lg-4"><div class="card"><div class="card-header"><h2 class="h6">{{ translate('Verified Referral Reward Rule') }}</h2></div><div class="card-body">
    <p class="text-muted small">{{ translate('Coins are credited only when the referred member has completed registration and verified their email. Duplicate and self-referrals are excluded.') }}</p>
    <form method="POST" action="{{ route('admin.referrals.reward_settings') }}">@csrf
      <div class="form-group"><label for="coins">{{ translate('Coins per eligible referral') }}</label><input id="coins" name="coin_reward" type="number" min="0" max="1000" required class="form-control" value="{{ old('coin_reward', $rewardRule?->coin_reward ?? 0) }}"></div>
      <div class="form-group"><label for="max-per-user">{{ translate('Maximum rewarded referrals per member') }}</label><input id="max-per-user" name="max_per_user" type="number" min="1" max="10000" required class="form-control" value="{{ old('max_per_user', $rewardRule?->max_per_user ?? 25) }}"></div>
      <label><input type="checkbox" name="active" value="1" @checked(old('active',$rewardRule?->active ?? false))> {{ translate('Enable coin rewards') }}</label>
      <button class="btn btn-primary btn-block mt-3" type="submit">{{ translate('Save reward settings') }}</button>
    </form>
    <p class="small mt-3">{{ translate('Run the scheduler to automatically award eligible referrals; no rewards are issued while disabled.') }}</p>
  </div></div></div>
  <div class="col-lg-8"><div class="row mb-3"><div class="col-sm-6"><div class="card"><div class="card-body"><strong>{{ $totalReferrals }}</strong><p class="text-muted mb-0">{{ translate('Registered referrals') }}</p></div></div></div><div class="col-sm-6"><div class="card"><div class="card-body"><strong>{{ $totalCoins }}</strong><p class="text-muted mb-0">{{ translate('Total coins credited') }}</p></div></div></div></div>
    <div class="card"><div class="card-header"><h2 class="h6">{{ translate('Recent reward transactions') }}</h2></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>{{ translate('Member ID') }}</th><th>{{ translate('Referred ID') }}</th><th>{{ translate('Coins') }}</th><th>{{ translate('Date') }}</th></tr></thead><tbody>
    @forelse($rewardHistory as $item)<tr><td>{{ $item->user_id }}</td><td>{{ $item->reference_id }}</td><td>+{{ $item->coins }}</td><td>{{ $item->created_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="4">{{ translate('No referral coin rewards yet.') }}</td></tr>@endforelse
    </tbody></table></div></div>
  </div>
</div>
<div class="card"><div class="card-header"><h2 class="h6">{{ translate('Recent referred members') }}</h2></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>{{ translate('Member') }}</th><th>{{ translate('Referrer ID') }}</th><th>{{ translate('Verified') }}</th><th>{{ translate('Joined') }}</th></tr></thead><tbody>
@forelse($referrals as $person)<tr><td>{{ $person->first_name }} {{ $person->last_name }} (#{{ $person->id }})</td><td>{{ $person->referred_by }}</td><td>{{ $person->email_verified_at ? translate('Yes') : translate('No') }}</td><td>{{ $person->created_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="4">{{ translate('No referrals yet.') }}</td></tr>@endforelse
</tbody></table>{{ $referrals->links() }}</div></div>
@endsection
