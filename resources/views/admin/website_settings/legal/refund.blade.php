@extends('admin.layouts.app')
@section('content')
<div class="container-fluid" style="max-width:1160px;padding-top:16px">
<div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
 <div><h2 class="h4">Refund Policy — Content Management</h2><p class="text-muted">Edit the live policy without deployment. Save Draft does not affect the published content.</p></div>
 <a class="btn btn-outline-primary" href="{{ route('legal.refund') }}" target="_blank" rel="noopener">Open live policy</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Changes were not saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card mb-3"><div class="card-body">
 <strong>Published version:</strong>
 @if($lastPublished)
   {{ $lastPublished->published_at?->format('d M Y, H:i') }} · Admin #{{ $lastPublished->editor_id }}
 @else
   Approved initial policy from client specification (no CMS changes published yet)
 @endif
 <div class="mt-2"><strong>Required refund commitment:</strong> 7 working days after eligibility verification and approval.</div>
</div></div>
<form method="post" action="{{ route('website.legal.refund.save') }}" id="hq-legal-editor">
@csrf
<div class="card mb-3"><div class="card-header"><strong>Page SEO & Title</strong></div><div class="card-body">
  @foreach(['title'=>'Page Title (H1)','meta_title'=>'SEO Meta Title','meta_description'=>'SEO Meta Description'] as $field=>$label)
   <div class="form-group"><label for="legal-{{ $field }}">{{ $label }}</label>
    @if($field==='meta_description')
    <textarea id="legal-{{ $field }}" class="form-control" name="{{ $field }}" maxlength="500" rows="2" required>{{ old($field,$data[$field]) }}</textarea>
    @else
    <input id="legal-{{ $field }}" class="form-control" name="{{ $field }}" value="{{ old($field,$data[$field]) }}" maxlength="170" required>
    @endif
   </div>
  @endforeach
</div></div>
<div class="card mb-3"><div class="card-header d-flex align-items-center justify-content-between flex-wrap">
 <strong>Policy Sections</strong>
 <button type="button" class="btn btn-sm btn-outline-primary" id="hq-add-section">+ Add Section</button>
</div><div class="card-body" id="hq-policy-sections">
 @foreach(old('sections',$data['sections']) as $i=>$section)
 <article class="border rounded p-3 mb-3 hq-policy-section" data-position="{{ $i }}">
   <div class="d-flex justify-content-between align-items-center mb-2">
     <strong class="hq-section-number">Section {{ $i+1 }}</strong>
     <div class="d-flex" style="gap:5px">
       <button type="button" class="btn btn-sm btn-light hq-move-up" title="Move earlier">↑</button>
       <button type="button" class="btn btn-sm btn-light hq-move-down" title="Move later">↓</button>
       <button type="button" class="btn btn-sm btn-outline-danger hq-remove-section">Remove</button>
     </div>
   </div>
   <div class="form-group"><label>Heading</label><input class="form-control hq-heading" name="sections[{{ $i }}][heading]" maxlength="180" value="{{ $section['heading'] }}" required></div>
   <div class="form-group"><label>Body (safe HTML with headings, paragraphs, links and lists)</label>
    <textarea class="form-control hq-body" name="sections[{{ $i }}][body]" rows="7" maxlength="60000">{{ $section['body'] }}</textarea></div>
 </article>
 @endforeach
</div></div>
<div class="card mb-3"><div class="card-header"><strong>Refund Turnaround Time</strong></div><div class="card-body">
 <p class="text-muted">The approved commitment is to initiate eligible refunds within 7 working days, after verification and approval. Preserve this statement when editing.</p>
 <div class="form-group"><label>Highlighted Timeline Heading</label><input class="form-control" name="turnaround_heading" maxlength="180" required value="{{ old('turnaround_heading',$data['turnaround_heading']) }}"></div>
 <div class="form-group"><label>Full Refund Processing Statement (HTML)</label><textarea class="form-control" name="turnaround_body" rows="9" maxlength="60000" required>{{ old('turnaround_body',$data['turnaround_body']) }}</textarea></div>
</div></div>
<div class="text-right mb-4" style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap">
 <button type="submit" name="action" value="draft" class="btn btn-outline-primary">Save Draft</button>
 <button type="submit" name="action" value="publish" class="btn btn-primary" onclick="return confirm('Publish this refund policy to the live website?')">Publish / Update</button>
</div>
</form>
<div class="card mb-5"><div class="card-header"><strong>Revision History</strong></div><div class="card-body table-responsive">
 <table class="table"><thead><tr><th>Revision</th><th>Status</th><th>Saved</th><th>Admin</th><th>Actions</th></tr></thead><tbody>
 @forelse($revisions as $revision)
  <tr><td>#{{ $revision->id }}</td><td>{{ ucfirst($revision->status) }}</td><td>{{ $revision->created_at?->format('d M Y H:i') }}</td><td>{{ $revision->editor?->first_name }} {{ $revision->editor?->last_name }}</td>
  <td>
   <a class="btn btn-sm btn-outline-primary" href="{{ route('website.legal.refund.preview',$revision) }}" target="_blank" rel="noopener">Preview</a>
   @if($revision->status==='published')
   <form class="d-inline" method="POST" action="{{ route('website.legal.restore',['page'=>'refund','revision'=>$revision]) }}">@csrf<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Restore this version and publish it?')">Restore</button></form>
   @endif
  </td></tr>
 @empty<tr><td colspan="5">No saved revisions yet.</td></tr>@endforelse
 </tbody></table>
</div></div>
</div>
<script>
(function(){
 const form=document.getElementById('hq-legal-editor'),wrap=document.getElementById('hq-policy-sections');
 if(!form||!wrap)return;
 let submitting=false,dirty=false;
 const mark=()=>{dirty=true};
 form.addEventListener('input',mark);
 function renumber(){
  [...wrap.children].forEach((el,i)=>{
   el.querySelector('.hq-section-number').textContent='Section '+(i+1);
   el.querySelector('.hq-heading').name='sections['+i+'][heading]';
   el.querySelector('.hq-body').name='sections['+i+'][body]';
  });
 }
 document.getElementById('hq-add-section').addEventListener('click',()=>{
  if(wrap.children.length>=25){alert('Maximum 25 sections');return}
  const clone=wrap.firstElementChild.cloneNode(true);
  clone.querySelector('.hq-heading').value='';
  clone.querySelector('.hq-body').value='<p></p>';
  wrap.appendChild(clone);renumber();mark();clone.scrollIntoView({block:'center'});
 });
 wrap.addEventListener('click',e=>{
  const row=e.target.closest('.hq-policy-section');if(!row)return;
  if(e.target.closest('.hq-remove-section')){
   if(wrap.children.length<2){alert('Keep at least one section');return}
   if(confirm('Remove this section?')){row.remove();mark();}
  }
  if(e.target.closest('.hq-move-up')&&row.previousElementSibling){wrap.insertBefore(row,row.previousElementSibling);mark()}
  if(e.target.closest('.hq-move-down')&&row.nextElementSibling){wrap.insertBefore(row.nextElementSibling,row);mark()}
  renumber();
 });
 form.addEventListener('submit',()=>submitting=true);
 window.addEventListener('beforeunload',e=>{if(dirty&&!submitting){e.preventDefault();e.returnValue='';}});
})();
</script>
@endsection
