@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-3 mb-3"><a href="{{ route('website.team_members.index') }}" class="d-inline-block mb-2"><i class="las la-arrow-left"></i> Back to Team Members</a><h1 class="h3">{{ $member ? 'Edit Team Member' : 'Add New Team Member' }}</h1><p class="text-muted">{{ $member ? 'Update the selected member details.' : 'Create a new member using the blank form below.' }}</p></div>
@if($errors->any())<div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ $member ? route('website.team_members.update',$member) : route('website.team_members.store') }}">
@csrf
@if($member) @method('PATCH') @endif
@include('admin.website_settings.team_member_fields',['item'=>$member])
<div class="d-flex align-items-center mt-3" style="gap:10px"><button class="btn btn-primary" type="submit">{{ $member ? 'Save Changes' : 'Create Team Member' }}</button><a class="btn btn-light" href="{{ route('website.team_members.index') }}">Cancel</a></div>
</form>
</div></div>
@endsection