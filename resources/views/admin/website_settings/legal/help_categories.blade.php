@extends('admin.layouts.app')
@section('content')
<div class="container-fluid" style="max-width:850px;padding:20px 0">
<h2 class="h4">Help Center Categories</h2>
<p class="text-muted">Add, remove or reorder categories by editing the list, one category per line. Publish changes to update the Help Center immediately.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('website.help.categories.save') }}" id="hq-help-categories-form">@csrf
<div class="form-group"><label>Category Names — one per line</label>
<textarea class="form-control" rows="12" name="categories" maxlength="4000" required>{{ old('categories',implode("\n",$items)) }}</textarea></div>
<div class="d-flex" style="gap:10px"><button class="btn btn-outline-primary" name="action" value="draft">Save Draft</button>
<button class="btn btn-primary" name="action" value="publish" onclick="return confirm('Publish updated Help categories?')">Publish Categories</button></div>
</form>
</div></div>
<div class="card mt-3"><div class="card-header">Category Revision History</div><div class="card-body">
@forelse($revisions as $revision)
<p>#{{ $revision->id }} — {{ $revision->status }} — {{ $revision->created_at?->format('d M Y H:i') }} by {{ $revision->editor?->first_name ?? 'Admin' }}
@if($revision->status === 'published')
<form class="d-inline" method="POST" action="{{ route('website.help.restore',$revision) }}">@csrf<button class="btn btn-sm btn-outline-primary" onclick="return confirm('Restore these published categories?')">Restore</button></form>
@endif</p>
@empty<p>No previous revisions.</p>@endforelse
</div></div>
<a class="btn btn-link mt-2" href="{{ route('website.help.manage') }}">Back to Help Center</a>
</div>
<script>
(function(){const f=document.getElementById('hq-help-categories-form');let dirty=false,saving=false;f.addEventListener('input',()=>dirty=true);f.addEventListener('submit',()=>saving=true);window.addEventListener('beforeunload',e=>{if(dirty&&!saving){e.preventDefault();e.returnValue='';}})})();
</script>
@endsection
