@extends('admin.layouts.app')
@section('content')
<div class="container-fluid" style="max-width:950px;padding:20px 0 50px">
<div class="d-flex justify-content-between flex-wrap mb-3"><div><h2 class="h4">Manage Help Article</h2><p class="text-muted">Public URL: /help-center/{{ $slug }}</p></div><a class="btn btn-light" href="{{ route('website.help.manage') }}">Back to Help Center</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('website.help.save') }}" id="hq-help-editor">
@csrf<input type="hidden" name="slug" value="{{ $slug }}">
<div class="card mb-3"><div class="card-body">
<div class="form-group"><label>Article / FAQ Title</label><input name="title" class="form-control" maxlength="180" required value="{{ old('title',$data['title']) }}"></div>
<div class="form-group"><label>Category</label><input name="category" class="form-control" maxlength="100" required list="hq-help-categories" value="{{ old('category',$data['category']) }}"><datalist id="hq-help-categories">@foreach($categories as $category)<option value="{{ $category }}"></option>@endforeach</datalist></div>
<div class="form-group"><label>Short Description</label><textarea class="form-control" name="description" maxlength="500" rows="2">{{ old('description',$data['description']) }}</textarea></div>
<div class="form-group"><label>Body — formatted HTML</label>
<textarea class="form-control aiz-text-editor" data-min-height="250" name="body" rows="12" maxlength="60000" required>{{ old('body',$data['body']) }}</textarea>
<small class="text-muted">Supports headings, paragraphs, bold, lists and safe HTTPS links. Script/embedded content is removed automatically.</small>
</div>
<div class="form-group"><label><input type="checkbox" name="is_faq" value="1" @checked(old('is_faq',$data['is_faq']))> This is a Frequently Asked Question</label></div>
<div class="form-group"><label>Display Order</label><input type="number" min="0" max="99999" class="form-control" style="max-width:180px" name="sort_order" value="{{ old('sort_order',$data['sort_order']) }}"></div>
<div class="d-flex flex-wrap" style="gap:9px">
 <button class="btn btn-outline-primary" name="action" value="draft">Save Draft</button>
 <button class="btn btn-primary" name="action" value="publish" onclick="return confirm('Publish this article to the Help Center?')">Publish / Update</button>
 <button class="btn btn-outline-danger" name="action" value="unpublish" onclick="return confirm('Unpublish this article?')">Unpublish</button>
</div>
</div></div>
</form>
<div class="card"><div class="card-header">Revision History (last 30)</div><div class="card-body table-responsive">
<table class="table"><thead><tr><th>Revision</th><th>Status</th><th>Updated</th><th>Admin</th><th>Actions</th></tr></thead><tbody>
@forelse($revisions as $revision)
<tr><td>#{{ $revision->id }}</td><td>{{ $revision->status }}</td><td>{{ $revision->created_at?->format('d M Y H:i') }}</td><td>{{ $revision->editor?->first_name }}</td>
<td><a class="btn btn-sm btn-outline-primary" href="{{ route('website.help.preview',$revision) }}" target="_blank">Preview</a>
@if($revision->status==='published')<form class="d-inline" method="POST" action="{{ route('website.help.restore',$revision) }}">@csrf<button class="btn btn-sm btn-light" onclick="return confirm('Restore this published revision?')">Restore</button></form>@endif</td></tr>
@empty<tr><td colspan="5">No revision yet.</td></tr>@endforelse
</tbody></table></div></div>
</div>
<script>
(function(){
 const form=document.getElementById('hq-help-editor');if(!form)return;
 let dirty=false,saving=false;
 form.addEventListener('input',()=>dirty=true);
 form.addEventListener('submit',()=>saving=true);
 window.addEventListener('beforeunload',e=>{if(dirty&&!saving){e.preventDefault();e.returnValue='';}});
})();
</script>
@endsection
