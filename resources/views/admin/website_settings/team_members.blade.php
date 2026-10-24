@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-3 mb-3"><h1 class="h3">Our Team — CMS Members</h1><p>Manage the homepage carousel and Our Team profiles. Add, update, reorder, hide, or remove team members. Initial records are imported once using the team seeder; review names and claims before publishing.</p></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@foreach($members as $member)
<div class="card mb-3"><div class="card-header"><strong>{{ $member->name }}</strong><span class="ml-2 text-muted">Order #{{ $member->sort_order }}</span></div><div class="card-body">
<form method="POST" action="{{ route('website.team_members.update',$member) }}">@csrf @method('PATCH')
@include('admin.website_settings.team_member_fields',['item'=>$member])
<button type="submit" class="btn btn-primary">Save Member</button></form>
<form action="{{ route('website.team_members.destroy',$member) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this member?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete Member</button></form></div></div>
@endforeach
<div class="card"><div class="card-header"><strong>Add Team Member</strong></div><div class="card-body">
<form method="POST" action="{{ route('website.team_members.store') }}">@csrf
@include('admin.website_settings.team_member_fields',['item'=>null])
<button type="submit" class="btn btn-primary">Add Member</button></form></div></div>
@endsection