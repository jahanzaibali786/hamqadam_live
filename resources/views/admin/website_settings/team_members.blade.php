@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-3 mb-3 d-flex align-items-center justify-content-between flex-wrap">
 <div><h1 class="h3 mb-1">Our Team Members</h1><p class="text-muted mb-0">Manage homepage carousel and Our Team page profiles.</p></div>
 <a class="btn btn-primary mt-2" href="{{ route('website.team_members.create') }}"><i class="las la-plus mr-1"></i> Add New Team Member</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row mb-3"><div class="col-md-6"><div class="card p-3"><strong>{{ number_format($totalMembers) }}</strong><span>Total team members</span></div></div><div class="col-md-6"><div class="card p-3"><strong>{{ number_format($activeMembers) }}</strong><span>Visible on website</span></div></div></div>
<div class="card"><div class="card-header d-block">
 <form method="GET" action="{{ route('website.team_members.index') }}" class="row align-items-center">
  <div class="col-md-6 mb-2"><input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="Search by name, role, location or group"></div>
  <div class="col-md-3 mb-2"><select name="status" class="form-control"><option value="">All statuses</option><option value="active" @selected($status==='active')>Visible</option><option value="hidden" @selected($status==='hidden')>Hidden</option></select></div>
  <div class="col-md-3 mb-2"><button type="submit" class="btn btn-primary">Search</button><a href="{{ route('website.team_members.index') }}" class="btn btn-light">Reset</a></div>
 </form>
</div><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Photo</th><th>Name / Role</th><th>Group</th><th>Location</th><th>Order</th><th>Status</th><th class="text-right">Actions</th></tr></thead><tbody>
@forelse($members as $member)
<tr>
 <td>@if($member->image_id)<img src="{{ uploaded_asset($member->image_id) }}" alt="" style="width:46px;height:46px;border-radius:50%;object-fit:cover" loading="lazy">@else<span class="avatar avatar-sm bg-soft-primary text-primary" style="display:inline-flex;align-items:center;justify-content:center">{{ $member->initials ?: mb_substr($member->name,0,2) }}</span>@endif</td>
 <td><strong>{{ $member->name }}</strong><div class="small text-muted">{{ $member->role }}</div></td>
 <td>{{ $member->group }}</td><td>{{ $member->location ?: '—' }}</td><td>{{ $member->sort_order }}</td>
 <td><span class="badge {{ $member->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $member->is_active ? 'Visible' : 'Hidden' }}</span></td>
 <td class="text-right"><div class="d-inline-flex align-items-center" style="gap:8px"><a class="btn btn-sm btn-soft-primary" href="{{ route('website.team_members.edit',$member) }}" aria-label="Edit {{ $member->name }}"><i class="las la-edit mr-1"></i>Edit</a><form method="POST" action="{{ route('website.team_members.destroy',$member) }}" onsubmit="return confirm('Delete this team member? This cannot be undone.')">@csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger" type="submit" aria-label="Delete {{ $member->name }}"><i class="las la-trash mr-1"></i>Delete</button></form></div></td>
</tr>
@empty
<tr><td colspan="7" class="text-center p-5">No team members found. <a href="{{ route('website.team_members.create') }}">Add the first member</a>.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $members->links() }}</div>
</div></div>
@endsection