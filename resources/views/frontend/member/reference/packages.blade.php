@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference hm-parity-packages"><header class="hm-reference-heading"><div><span class="hm-ref-kicker">Membership</span><h1>Packages &amp; Plans</h1><p>Choose the plan that helps you find your perfect match.</p></div></header>
<div class="hm-parity-filter-tabs"><a class="current" href="#subscription-plans">Subscription Plans</a><a href="#feature-comparison">Feature Comparison</a><a href="{{ route('wallet.index') }}">Coins &amp; Wallet</a></div>
<div class="hm-parity-editorial-grid"><main><div id="subscription-plans" class="hm-ref-plans">
@forelse($packages as $package)
@php($current=(int)($member?->current_package_id??0)===(int)$package->id && (!$member?->package_validity || \Carbon\Carbon::parse($member->package_validity)->endOfDay()->gte(now())))
<article class="hm-ref-plan @if($current) is-current @endif">
<div class="hm-ref-plan-icon"><i class="las la-{{ strtolower((string)$package->name)==='free'?'user':(strtolower((string)$package->name)==='gold'?'crown':'gem') }}"></i></div><h2>{{ $package->name }}</h2><p>{{ $package->description??'Find meaningful connections with Hamqadam.' }}</p><div class="hm-ref-price">{{ single_price($package->price) }}</div><p>{{ $package->validity }} days</p>
<ul><li>✓ {{ (int)$package->express_interest }} interest coins</li><li>✓ {{ (int)$package->contact }} contact views</li><li>✓ {{ (int)$package->photo_gallery }} gallery uploads</li><li>✓ {{ (int)$package->profile_viewers_view }} profile views</li><li>✓ {{ $package->auto_profile_match?'Automated matching':'Member discovery' }}</li></ul>
@if($current)<span class="hm-ref-plan-current">Current Plan</span>@else<a class="hm-ref-cta" href="{{ route('package_payment_methods',encrypt($package->id)) }}">Choose {{ $package->name }} →</a>@endif
</article>
@empty<div class="hm-ref-white">No membership packages are available.</div>@endforelse
</div>
<section class="hm-ref-white mt-3" id="feature-comparison"><h2>Feature Comparison</h2><div class="table-responsive"><table class="table table-sm hm-parity-feature-table"><thead><tr><th>Included Benefits</th>@foreach($packages as $p)<th>{{ $p->name }}</th>@endforeach</tr></thead><tbody>
@foreach([['Interest Coins','express_interest'],['Contact Views','contact'],['Gallery Uploads','photo_gallery'],['Profile Views','profile_viewers_view']] as $feature)
<tr><td>{{ $feature[0] }}</td>@foreach($packages as $p)<td>{{ (int)($p->{$feature[1]}??0) }}</td>@endforeach</tr>
@endforeach
<tr><td>Automated Profile Matching</td>@foreach($packages as $p)<td>{{ $p->auto_profile_match?'✓':'—' }}</td>@endforeach</tr></tbody></table></div></section></main>
<aside><section class="hm-ref-white"><h2><i class="las la-star"></i> Why Upgrade?</h2><p class="small text-muted">Plans provide varying amounts of coins, views and gallery features. Compare actual entitlements before purchasing.</p><a class="hm-ref-cta" href="#feature-comparison">Compare Benefits</a></section>
<section class="hm-ref-white"><h2>Payment &amp; Security</h2><p class="small text-muted">Payment options are presented during the existing secure checkout process. Actual provider availability depends on configuration.</p><a class="hm-ref-cta" href="{{ route('wallet.index') }}">Open My Wallet</a></section></aside></div>
</div>
@endsection