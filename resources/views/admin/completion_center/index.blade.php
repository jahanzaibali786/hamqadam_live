@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3"><h5 class="mb-0 h6">{{ translate('Completion, Engagement & Analytics Center') }}</h5></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row gutters-10">
@foreach(['Funnel registrations'=>$funnel['registration_completed']??0,'Got Match confirmations'=>$successCount,'NPS'=>$nps===null?'No responses':$nps,'D7 retention'=>$retention['d7'].'%'] as $label=>$value)
<div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="h3 mb-0">{{ $value }}</div></div></div></div>
@endforeach
</div>
<div class="card"><div class="card-header"><h6 class="mb-0">{{ translate('Funnel Analytics') }}</h6></div><div class="card-body"><div class="row">
@foreach($funnel as $stage=>$count)<div class="col-md-3 mb-3"><strong>{{ ucwords(str_replace('_',' ',$stage)) }}</strong><div class="h4">{{ $count }}</div></div>@endforeach
</div></div></div>
<div class="row">
<div class="col-lg-6"><div class="card"><div class="card-header"><h6 class="mb-0">Sponsored Listings / Ad Placement</h6></div><div class="card-body">
<form method="POST" action="{{ route('admin.completion.sponsored.store') }}">@csrf
<div class="form-group"><input class="form-control" name="title" placeholder="Listing title" required></div><div class="form-group"><input class="form-control" name="sponsor_name" placeholder="Sponsor name"></div><div class="form-group"><input class="form-control" name="target_url" placeholder="https://..."></div><div class="form-row"><div class="col"><input class="form-control" name="placement" value="discovery"></div><div class="col"><input class="form-control" type="number" name="priority" value="0"></div></div><label class="mt-2"><input type="checkbox" name="active" value="1" checked> Active</label><button class="btn btn-primary btn-block mt-2">Add Sponsored Listing</button></form>
<hr>@foreach($sponsored as $item)<div class="d-flex justify-content-between border-bottom py-2"><span><strong>{{ $item->title }}</strong><br><small>{{ $item->placement }} · {{ $item->active?'Active':'Inactive' }}</small></span><form method="POST" action="{{ route('admin.completion.sponsored.destroy',$item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger">Remove</button></form></div>@endforeach
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header"><h6 class="mb-0">Gamification & Reward Rules</h6></div><div class="card-body">
<form method="POST" action="{{ route('admin.completion.rewards.store') }}">@csrf<div class="form-group"><input class="form-control" name="event_key" placeholder="profile_completed" required></div><div class="form-group"><input class="form-control" name="title" placeholder="Profile completion reward" required></div><div class="form-row"><div class="col"><input class="form-control" type="number" min="0" name="coin_reward" value="10"></div><div class="col"><input class="form-control" type="number" min="1" name="max_per_user" value="1"></div></div><label class="mt-2"><input type="checkbox" name="active" value="1" checked> Active</label><button class="btn btn-primary btn-block mt-2">Save Reward Rule</button></form><hr>
@foreach($rewardRules as $rule)<div class="border-bottom py-2"><strong>{{ $rule->title }}</strong><span class="badge badge-soft-success float-right">{{ $rule->coin_reward }} coins</span><br><small>{{ $rule->event_key }} · max {{ $rule->max_per_user }}/user</small></div>@endforeach
</div></div></div>
</div>
<div class="row"><div class="col-lg-6"><div class="card"><div class="card-header"><h6 class="mb-0">Feature Adoption</h6></div><div class="card-body"><table class="table table-sm"><thead><tr><th>Feature</th><th>Events</th></tr></thead><tbody>@forelse($featureAdoption as $row)<tr><td>{{ $row->feature_key }}</td><td>{{ $row->total }}</td></tr>@empty<tr><td colspan="2">No tracked events yet.</td></tr>@endforelse</tbody></table></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header"><h6 class="mb-0">Geographic User Activity</h6></div><div class="card-body"><table class="table table-sm"><thead><tr><th>Country</th><th>Events</th></tr></thead><tbody>@forelse($geo as $row)<tr><td>{{ $row->country }}</td><td>{{ $row->total }}</td></tr>@empty<tr><td colspan="2">No geo events yet.</td></tr>@endforelse</tbody></table></div></div></div></div>
<div class="card"><div class="card-header"><h6 class="mb-0">Ad Gating</h6></div><div class="card-body"><form method="POST" action="{{ route('admin.completion.ads.update') }}">@csrf <label class="mr-4"><input type="checkbox" name="ads_free_plan_enabled" value="1" {{ $adSettings['free_ads']?'checked':'' }}> Show sponsored placements to Free/Basic users</label><label><input type="checkbox" name="ads_paid_plan_enabled" value="1" {{ $adSettings['paid_ads']?'checked':'' }}> Allow sponsored placements on paid plans</label><button class="btn btn-primary ml-3">Save</button></form></div></div>
@endsection
