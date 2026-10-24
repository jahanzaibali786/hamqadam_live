@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-3 mb-3"><h1 class="h3">Our Team — CMS Members</h1><p>Manage the homepage carousel and Our Team profiles. Add, update, reorder, hide, or remove team members. Initial records are imported once using the team seeder; review names and claims before publishing.</p></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="mb-3"><a class="btn btn-primary" href="#hq-team-add" id="hq-team-add-jump"><i class="las la-plus"></i> Add New Team Member</a><span class="ml-2 text-muted">Jump directly to the blank form below</span></div>
@foreach($members as $member)
<div class="card mb-3"><div class="card-header"><strong>{{ $member->name }}</strong><span class="ml-2 text-muted">Order #{{ $member->sort_order }}</span></div><div class="card-body">
<form method="POST" action="{{ route('website.team_members.update',$member) }}">@csrf @method('PATCH')
@include('admin.website_settings.team_member_fields',['item'=>$member])
<button type="submit" class="btn btn-primary">Save Member</button></form>
<form action="{{ route('website.team_members.destroy',$member) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this member?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete Member</button></form></div></div>
@endforeach
<div class="card" id="hq-team-add"><div class="card-header bg-soft-primary"><strong>Add New Team Member — Blank Form</strong></div><div class="card-body">
<form method="POST" action="{{ route('website.team_members.store') }}" id="hq-team-add-form">@csrf
@include('admin.website_settings.team_member_fields',['item'=>null])
<button type="submit" class="btn btn-primary">Add Member</button></form></div></div>
<script>document.getElementById('hq-team-add-jump')?.addEventListener('click',()=>{document.getElementById('hq-team-add')?.scrollIntoView({behavior:'smooth',block:'start'});document.querySelector('#hq-team-add-form input[name=name]')?.focus({preventScroll:true});});if(location.hash==='#hq-team-add'){document.getElementById('hq-team-add')?.scrollIntoView();}</script>
@endsection