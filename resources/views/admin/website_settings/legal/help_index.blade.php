@extends('admin.layouts.app')
@section('content')
<div class="container-fluid" style="max-width:1100px;padding-top:18px">
 <div class="d-flex align-items-start justify-content-between flex-wrap mb-3"><div><h2 class="h4">Help Center Content</h2><p class="text-muted">Manage published help articles and FAQs using the existing website support channels.</p></div>
 <div style="display:flex;gap:8px;flex-wrap:wrap">
 <a class="btn btn-outline-primary" href="{{ route('website.help.categories') }}">Manage Categories</a>
 <a class="btn btn-outline-primary" href="{{ route('help_center.index') }}" target="_blank">View Help Center</a>
 </div></div>
 <div class="card mb-3"><div class="card-header"><strong>Create a New Article or FAQ</strong></div><div class="card-body">
  <form action="{{ route('website.help.new') }}" method="GET" class="d-flex flex-wrap" style="gap:10px">
   <input class="form-control" name="slug" pattern="[a-z0-9-]{1,100}" required placeholder="unique-article-slug" style="max-width:320px">
   <button class="btn btn-primary">Create/Edit Article</button>
  </form>
  <small class="text-muted">Use a lowercase hyphenated URL slug. No new code deployment is required to publish new articles.</small>
 </div></div>
 <div class="card"><div class="card-header"><strong>Published Articles</strong></div><div class="card-body table-responsive">
 <table class="table"><thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Actions</th></tr></thead><tbody>
 @forelse($articles as $article)
 <tr><td>{{ $article['data']['title'] }}</td><td>{{ $article['data']['category'] }}</td><td>{{ $article['data']['is_faq'] ? 'FAQ' : 'Article' }}</td><td>
  <a class="btn btn-sm btn-outline-primary" href="{{ route('website.help.edit',$article['slug']) }}">Edit</a>
  <a class="btn btn-sm btn-light" href="{{ route('help_center.article',$article['slug']) }}" target="_blank">View</a></td></tr>
 @empty <tr><td colspan="4">No published articles yet. Create the first article above.</td></tr>@endforelse
 </tbody></table>
 </div></div>
 @if($drafts->count())
 <div class="card mt-3 mb-4"><div class="card-header"><strong>Draft Articles</strong></div><div class="card-body">
 @foreach($drafts as $draft)
  <a class="btn btn-outline-primary btn-sm mb-2" href="{{ route('website.help.edit',\Illuminate\Support\Str::after($draft->page_key,'help:')) }}">
    {{ json_decode($draft->content_json,true)['title'] ?? \Illuminate\Support\Str::after($draft->page_key,'help:') }}
  </a>
 @endforeach
 </div></div>
 @endif
</div>
@endsection
