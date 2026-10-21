@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-3"><h1 class="h3">Member Profile Search</h1><p>Search the entire member database, including location and recent activity filters.</p></div>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('admin.client.scope.profiles') }}" class="card mb-4"><div class="card-body row">
<div class="col-md-6 form-group"><label for="member-search">Name, ID, email, phone or profile code</label><input id="member-search" name="q" class="form-control" value="{{ request('q') }}" maxlength="120" placeholder="Search all members"></div>
<div class="col-md-2 form-group"><label for="gender-filter">Gender</label><select id="gender-filter" class="form-control" name="gender"><option value="">All</option><option value="1" @selected(request('gender')==='1')>Male</option><option value="2" @selected(request('gender')==='2')>Female</option></select></div>
<div class="col-md-2 form-group"><label for="country-filter">Country ID</label><input id="country-filter" type="number" min="1" class="form-control" name="country_id" value="{{ request('country_id') }}"></div>
<div class="col-md-2 form-group"><label for="city-filter">City ID</label><input id="city-filter" type="number" min="1" class="form-control" name="city_id" value="{{ request('city_id') }}"></div>
<div class="col-md-3 form-group"><label for="activity-filter">Recent activity</label><select id="activity-filter" name="activity" class="form-control"><option value="">Any</option><option value="active_7d" @selected(request('activity')==='active_7d')>Active within 7 days</option><option value="active_30d" @selected(request('activity')==='active_30d')>Active within 30 days</option><option value="never_active" @selected(request('activity')==='never_active')>No recorded activity</option></select></div>
<div class="col-md-3 form-group"><label for="visibility-filter">Profile visibility</label><select id="visibility-filter" name="visibility" class="form-control"><option value="">Any</option><option value="visible" @selected(request('visibility')==='visible')>Visible</option><option value="hidden" @selected(request('visibility')==='hidden')>Hidden</option></select></div>
<div class="col-md-3 form-group"><label for="sort">Sort</label><select id="sort" class="form-control" name="sort"><option value="newest">Newest</option><option value="oldest" @selected(request('sort')==='oldest')>Oldest</option><option value="name" @selected(request('sort')==='name')>Name</option><option value="last_active" @selected(request('sort')==='last_active')>Recently active</option></select></div>
<div class="col-md-3 form-group d-flex align-items-end"><button class="btn btn-primary btn-block">Search</button></div>
</div></form>
<div class="card"><div class="card-body"><p><strong>{{ $profiles->total() }}</strong> profile(s) matched.</p><div class="table-responsive"><table class="table table-striped">
<thead><tr><th>Profile</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Visible</th><th>Last active</th><th>Joined</th></tr></thead>
<tbody>@forelse($profiles as $profile)<tr><td>{{ $profile->code ?? $profile->id }}</td><td>{{ $profile->name }}</td><td>{{ $profile->email }}</td><td>{{ $profile->phone }}</td><td>{{ $profile->member?->gender }}</td><td>{{ $profile->member?->hide_profile ? 'Hidden' : 'Visible' }}</td><td>{{ $profile->last_active_at?->format('Y-m-d') ?? 'Not recorded' }}</td><td>{{ $profile->created_at?->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="8">No profiles found.</td></tr>@endforelse</tbody></table></div>{{ $profiles->links() }}</div></div>
@endsection
