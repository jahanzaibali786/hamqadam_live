@extends('admin.layouts.app')
@section('content')
<div class="container-fluid py-4">
 <h1 class="h3">Interest & Proposal Message Templates</h1><p>Edit the opening, body and closing separately. Inactive templates are hidden from members.</p>
 @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
 @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
 <section class="card mb-4 p-4"><h2 class="h5">Create a Template</h2><form method="POST" action="{{route('admin.workflow.templates.create')}}">@csrf
 <div class="row"><div class="col-md-3"><label>Type</label><select name="kind" class="form-control"><option value="interest">Interest</option><option value="proposal">Proposal</option></select></div><div class="col-md-3"><label>Language</label><input name="language" value="en" class="form-control" required></div><div class="col-md-4"><label>Title</label><input name="title" class="form-control" required></div><div class="col-md-2"><label>Order</label><input type="number" name="display_order" value="0" min="0" class="form-control"></div></div>
 <label class="mt-2">Opening</label><textarea name="opening" rows="2" class="form-control" required></textarea><label class="mt-2">Body</label><textarea name="body" rows="3" class="form-control" required></textarea><label class="mt-2">Closing</label><textarea name="closing" rows="2" class="form-control" required></textarea><input type="hidden" name="is_active" value="1"><button class="btn btn-primary mt-3" type="submit">Add Template</button></form></section>
 @foreach($templates as $template)
 <section class="card p-3 mb-3">
 <form method="POST" action="{{route('admin.workflow.templates.save',$template)}}">@csrf
 <div class="row"><div class="col-lg-2"><label>Type</label><select name="kind" class="form-control"><option value="interest" @selected($template->kind==='interest')>Interest</option><option value="proposal" @selected($template->kind==='proposal')>Proposal</option></select></div><div class="col-lg-2"><label>Language</label><input name="language" class="form-control" value="{{$template->language}}" required></div><div class="col-lg-5"><label>Title</label><input name="title" class="form-control" value="{{$template->title}}" required></div><div class="col-lg-1"><label>Order</label><input type="number" min="0" name="display_order" class="form-control" value="{{$template->display_order}}"></div><div class="col-lg-2"><label>Status</label><select name="is_active" class="form-control"><option value="1" @selected($template->is_active)>Active</option><option value="0" @selected(!$template->is_active)>Inactive</option></select></div></div>
 <label>Opening</label><textarea name="opening" class="form-control" rows="2" required>{{ $template->opening }}</textarea>
 <label>Body</label><textarea name="body" class="form-control" rows="3" required>{{ $template->body }}</textarea>
 <label>Closing</label><textarea name="closing" class="form-control" rows="2" required>{{ $template->closing }}</textarea>
 <p class="text-muted mt-2">Created by user #{{ $template->created_by ?? '—' }} · Updated by #{{ $template->updated_by ?? '—' }} · {{ $template->updated_at }}</p><button type="submit" class="btn btn-primary">Save Changes</button>
 </form>
 </section>
 @endforeach
 {{$templates->links()}}
</div>
@endsection
