@extends('admin.layouts.app')
@section('content')
<div class="container-fluid py-4">
  <h1 class="h3 mb-2">Referral Settings</h1>
  <p class="text-muted">Configure two independent referral coin rewards. These settings are available to administrators and sub-administrators.</p>
  @if(session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
  @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

  @unless($databaseReady)
    <div class="alert alert-warning" role="alert">
      <h2 class="h5">Referral database setup needs attention</h2>
      <p>The settings page is available, but saving rewards is disabled until the database is ready.</p>
      <ul>@foreach($setupIssues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
      <p>After backing up the database, run the pending migration and clear caches on your server:</p>
      <pre class="bg-white border rounded p-3">php artisan migrate --force
php artisan optimize:clear
php artisan view:clear</pre>
      <p class="mb-0">If the warning remains, inspect <code>storage/logs/laravel.log</code> for “Referral settings read failed”.</p>
    </div>
  @endunless

  <div class="row">
    <div class="col-lg-6 mb-4">
      <section class="card h-100">
        <div class="card-header"><h2 class="h5 mb-0">Registration Reward</h2></div>
        <div class="card-body">
          <p class="text-muted">Reward the referrer after an eligible referred member completes registration and verifies their email.</p>
          <form method="POST" action="{{ route('admin.referrals.reward_settings') }}">
            @csrf
            <div class="form-group"><label for="ref-registration-coins">Coins per eligible registration</label>
              <input class="form-control" type="number" min="0" max="1000" name="coin_reward" id="ref-registration-coins" value="{{ $rewardRule?->coin_reward ?? 0 }}" required @disabled(!$databaseReady)>
            </div>
            <div class="form-group"><label for="ref-registration-limit">Maximum registration rewards per referrer</label>
              <input class="form-control" type="number" min="-1" max="10000" name="max_per_user" id="ref-registration-limit" value="{{ $rewardRule?->max_per_user ?? 25 }}" required @disabled(!$databaseReady)>
              <small class="form-text text-muted">Set <strong>-1</strong> for unlimited registration referral rewards.</small>
            </div>
            <label><input type="checkbox" name="active" value="1" @checked($rewardRule?->active ?? false) @disabled(!$databaseReady)> Enable registration rewards</label>
            <button class="btn btn-primary btn-block mt-3" type="submit" @disabled(!$databaseReady)>Save Registration Reward</button>
          </form>
        </div>
      </section>
    </div>
    <div class="col-lg-6 mb-4">
      <section class="card h-100">
        <div class="card-header"><h2 class="h5 mb-0">Paid Package Activation Reward</h2></div>
        <div class="card-body">
          <p class="text-muted">Reward the referrer once when a referred member first activates a qualifying paid package other than the default Basic package. Free and unpaid packages do not qualify.</p>
          <form method="POST" action="{{ route('admin.referrals.package_reward_settings') }}">
            @csrf
            <div class="form-group"><label for="ref-package-coins">Coins per qualifying package activation</label>
              <input class="form-control" type="number" min="0" max="1000" name="coin_reward" id="ref-package-coins" value="{{ $packageRewardRule?->coin_reward ?? 0 }}" required @disabled(!$databaseReady)>
            </div>
            <div class="form-group"><label for="ref-package-limit">Maximum package rewards per referrer</label>
              <input class="form-control" type="number" min="-1" max="10000" name="max_per_user" id="ref-package-limit" value="{{ $packageRewardRule?->max_per_user ?? 25 }}" required @disabled(!$databaseReady)>
              <small class="form-text text-muted">Set <strong>-1</strong> for unlimited paid-package activation referral rewards.</small>
            </div>
            <label><input type="checkbox" name="active" value="1" @checked($packageRewardRule?->active ?? false) @disabled(!$databaseReady)> Enable paid package rewards</label>
            <button class="btn btn-primary btn-block mt-3" type="submit" @disabled(!$databaseReady)>Save Package Activation Reward</button>
          </form>
        </div>
      </section>
    </div>
  </div>
</div>
@endsection
