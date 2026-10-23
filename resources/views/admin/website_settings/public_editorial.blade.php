@extends('admin.layouts.app')
@section('content')
<div class="container-fluid" style="max-width:1060px"><div class="d-flex justify-content-between align-items-center my-3"><h4>{{ $page==='blogs'?'Blog & Articles':'Success Stories' }} Page Design Content</h4><a href="{{ $page==='blogs'?route('blog'):route('happy_stories') }}" class="btn btn-outline-primary" target="_blank">View Public Page</a></div>
<p>These controls manage the page design and labels. Publish articles in <a href="{{ route('blog.index') }}">Blog System → All Posts</a>, and approve stories in <a href="{{ route('happy-story.index') }}">Happy Stories</a>.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('website.public_editorial.update',$page) }}">@csrf
@foreach($data as $key=>$value)
@if(is_array($value))
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ ucwords(str_replace('_',' ',$key)) }}</h5></div><div class="card-body">
@foreach($value as $index=>$row)<div class="row p-3 mb-2 border rounded">@foreach($row as $sub=>$text)<div class="col-md-6 form-group"><label>{{ ucwords(str_replace('_',' ',$sub)) }} #{{ $index+1 }}</label><textarea class="form-control" rows="2" maxlength="1500" name="{{ $key }}[{{ $index }}][{{ $sub }}]">{{ old("$key.$index.$sub",$text) }}</textarea></div>@endforeach</div>@endforeach
</div></div>
@else
<div class="card mb-2"><div class="card-body"><label class="font-weight-bold">{{ ucwords(str_replace('_',' ',$key)) }}</label>@if(mb_strlen($value)>115)<textarea class="form-control" rows="3" maxlength="1500" name="{{ $key }}">{{ old($key,$value) }}</textarea>@else<input name="{{ $key }}" maxlength="1500" class="form-control" value="{{ old($key,$value) }}">@endif</div></div>
@endif
@endforeach
<button class="btn btn-primary btn-lg my-4" type="submit">Save Page Content</button></form></div>
@endsection