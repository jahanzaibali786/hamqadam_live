@extends('admin.layouts.app')

@section('content')
<div class="container-fluid" style="max-width:1250px;padding-top:20px">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div><h2>Member Advertisement Sliders</h2>
            <p class="text-muted mb-1">Manage the top hero banner and the lower sponsored banner on Dashboard, Matched Profiles, and Member Home.</p>
            <p class="mb-0"><strong>Each placement supports 0–{{ $maximum }} slides.</strong> Three starter banners are included in each placement. Disable or remove any starter when ready to publish your own.</p>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Changes were not saved:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('website.member_ad_sliders.update') }}" id="hq-ad-editor">@csrf
        @foreach(['top'=>'Top Matchmaking Hero Slider','bottom'=>'Footer Sponsored Banner Slider'] as $placement=>$label)
        <section class="card mb-4 hq-ad-admin-section" data-placement="{{ $placement }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <div><h3 class="h5 mb-1">{{ $label }}</h3><small>{{ $placement==='top'?'Recommended source artwork: 1800 × 300px (6:1).':'Recommended source artwork: 1800 × 150px (12:1).' }} Display images at least 1600px wide for desktop. Responsive crop is automatic.</small></div>
                <button type="button" class="btn btn-outline-primary hq-ad-add" data-placement="{{ $placement }}"><i class="las la-plus"></i> Add Slide <span class="hq-ad-counter"></span></button>
            </div>
            <div class="card-body">
                <p class="text-muted">Upload a single image per slide using the existing media library. Change the horizontal/vertical position sliders to adjust the image inside the visible banner. Image itself is not modified or cropped on disk.</p>
                <div class="hq-ad-rows" data-placement="{{ $placement }}">
                    @foreach(old('slides.'.$placement, $placements[$placement]) as $index=>$slide)
                    @php
                        $preset = $slide['preset'] ?? '';
                        $existingId = $slide['image_id'] ?? '';
                        $previewImage = $existingId ? uploaded_asset($existingId) : (isset($presets[$preset]) ? static_asset($presets[$preset]) : '');
                    @endphp
                    <article class="hq-ad-editor-row border rounded mb-3 p-3" data-index="{{ $index }}">
                        <div class="d-flex justify-content-between align-items-center mb-2"><strong class="hq-ad-row-title">Slide {{ $loop->iteration }}</strong><div><button type="button" class="btn btn-sm btn-light hq-ad-up" title="Move earlier" aria-label="Move slide earlier"><i class="las la-arrow-up"></i></button> <button type="button" class="btn btn-sm btn-light hq-ad-down" title="Move later" aria-label="Move slide later"><i class="las la-arrow-down"></i></button> <button type="button" class="btn btn-sm btn-outline-danger hq-ad-remove">Remove</button></div></div>
                        <div class="row">
                            <div class="col-lg-6">
                                <label>Image from Media Library</label>
                                <div class="input-group" data-toggle="aizuploader" data-type="image">
                                    <div class="input-group-prepend"><span class="input-group-text">Browse</span></div>
                                    <div class="form-control file-amount">Choose banner image</div>
                                    <input type="hidden" class="selected-files hq-ad-upload" name="slides[{{ $placement }}][{{ $index }}][image_id]" value="{{ $existingId }}">
                                </div>
                                <div class="file-preview box"></div>
                                <label class="mt-2">Starter Image (used only if no upload selected)</label>
                                <select class="form-control hq-ad-preset" name="slides[{{ $placement }}][{{ $index }}][preset]">
                                    <option value="">None — use uploaded image</option>
                                    @foreach($presets as $key=>$value)<option value="{{ $key }}" data-image="{{ static_asset($value) }}" @selected($key===$preset)>{{ ucwords(str_replace('_',' ',$key)) }}</option>@endforeach
                                </select>
                                <div class="hq-ad-preview mt-3 {{ $placement==='top'?'hq-ad-preview-top':'hq-ad-preview-bottom' }}">
                                    <img class="hq-ad-preview-image" src="{{ $previewImage }}" alt="Banner crop preview" style="object-position:{{ (int)($slide['object_x']??50) }}% {{ (int)($slide['object_y']??50) }}%">
                                    <div class="hq-ad-preview-label">Live image framing</div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-sm-6"><label>Horizontal framing: <output class="hq-ad-x-label">{{ (int)($slide['object_x']??50) }}%</output></label><input type="range" class="custom-range hq-ad-x" min="0" max="100" name="slides[{{ $placement }}][{{ $index }}][object_x]" value="{{ (int)($slide['object_x']??50) }}"></div>
                                    <div class="col-sm-6"><label>Vertical framing: <output class="hq-ad-y-label">{{ (int)($slide['object_y']??50) }}%</output></label><input type="range" class="custom-range hq-ad-y" min="0" max="100" name="slides[{{ $placement }}][{{ $index }}][object_y]" value="{{ (int)($slide['object_y']??50) }}"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                @foreach(['eyebrow'=>'Small Label','title'=>'Banner Headline','highlight'=>'Highlighted Line','subtitle'=>'Supporting Copy','button_label'=>'Call-to-Action Label','button_url'=>'Call-to-Action URL (relative /path or HTTPS)'] as $field=>$text)
                                <label class="d-block mb-1">{{ $text }}</label>
                                <input class="form-control mb-2" type="text" name="slides[{{ $placement }}][{{ $index }}][{{ $field }}]" value="{{ $slide[$field]??'' }}" maxlength="{{ $field==='subtitle'?230:($field==='button_url'?500:110) }}" @if($field==='title') required @endif>
                                @endforeach
                                <label class="mt-2"><input type="checkbox" class="hq-ad-active" name="slides[{{ $placement }}][{{ $index }}][active]" value="1" @checked((bool)($slide['active']??true))> Enabled / show on website</label>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
        @endforeach
        <div class="text-right mb-5"><button type="submit" class="btn btn-primary btn-lg">Save Both Sliders</button></div>
    </form>
</div>
{{-- A local form clone creates new editable entries without extra server trips.
     The rows are built only from known template fields, no dynamic HTML from users. --}}
<style>
.hq-ad-preview{position:relative;width:100%;border-radius:12px;background:#F6F4F5;border:1px solid #E5E5E7;overflow:hidden}
.hq-ad-preview-top{aspect-ratio:6/1}.hq-ad-preview-bottom{aspect-ratio:12/1}
.hq-ad-preview img{display:block;width:100%;height:100%;object-fit:cover}
.hq-ad-preview-label{position:absolute;bottom:5px;left:8px;background:#FFFFFF;color:#A45F7B;padding:3px 8px;border-radius:5px;font-size:10px}
.hq-ad-editor-row{background:#FFFFFF}.hq-ad-editor-row input[type=range]{accent-color:#A45F7B}
</style>
@endsection

@section('script')
<script>
(function(){
 const root=document.querySelector('#hq-ad-editor');if(!root)return;
 const max=6;
 function rowsOf(type){return root.querySelector('.hq-ad-rows[data-placement="'+type+'"]')}
 function sync(section){
  const type=section.dataset.placement,items=[...rowsOf(type).children];
  items.forEach((row,i)=>{
   row.dataset.index=i;row.querySelector('.hq-ad-row-title').textContent='Slide '+(i+1);
   row.querySelectorAll('[name]').forEach(input=>{
    input.name=input.name.replace(/slides\[(?:top|bottom)\]\[\d+\]/,'slides['+type+']['+i+']');
   });
  });
  const add=section.querySelector('.hq-ad-add');
  add.disabled=items.length>=max;add.querySelector('.hq-ad-counter').textContent='('+items.length+'/'+max+')';
 }
 function preview(row){
  const x=row.querySelector('.hq-ad-x'),y=row.querySelector('.hq-ad-y');
  row.querySelector('.hq-ad-x-label').textContent=x.value+'%';
  row.querySelector('.hq-ad-y-label').textContent=y.value+'%';
  row.querySelector('.hq-ad-preview-image').style.objectPosition=x.value+'% '+y.value+'%';
 }
 function resetRow(row){
  row.querySelectorAll('input[type=text]').forEach(input=>input.value='');
  row.querySelector('.hq-ad-upload').value='';
  const firstImage=row.querySelector('.hq-ad-preset option:not([value=""])');
  const select=row.querySelector('.hq-ad-preset');select.value=firstImage?.value||'';
  row.querySelector('.hq-ad-preview-image').src=firstImage?.dataset.image||'';
  row.querySelector('.hq-ad-x').value='50';row.querySelector('.hq-ad-y').value='50';
  row.querySelector('.hq-ad-active').checked=true;
  row.querySelector('.file-preview').textContent='';
  preview(row);
 }
 root.addEventListener('click',e=>{
  const add=e.target.closest('.hq-ad-add'),remove=e.target.closest('.hq-ad-remove');
  if(add){
   const section=add.closest('.hq-ad-admin-section'),wrap=rowsOf(section.dataset.placement);
   if(wrap.children.length>=max)return;
   const model=wrap.firstElementChild;
   if(!model){alert('Keep one slide in each placement while editing; add slides before removing the last one.');return;}
   const clone=model.cloneNode(true);resetRow(clone);wrap.appendChild(clone);sync(section);
  }
  const up=e.target.closest('.hq-ad-up'),down=e.target.closest('.hq-ad-down');
  if(up||down){
   const row=(up||down).closest('.hq-ad-editor-row'),section=row.closest('.hq-ad-admin-section');
   if(up&&row.previousElementSibling)row.parentNode.insertBefore(row,row.previousElementSibling);
   if(down&&row.nextElementSibling)row.parentNode.insertBefore(row.nextElementSibling,row);
   sync(section);
  }
  if(remove){
   const section=remove.closest('.hq-ad-admin-section'),wrap=rowsOf(section.dataset.placement);
   if(wrap.children.length<=1){alert('Keep at least one editable slide. Disable it to hide the slider.');return;}
   remove.closest('.hq-ad-editor-row').remove();sync(section);
  }
 });
 root.addEventListener('input',e=>{
  const row=e.target.closest('.hq-ad-editor-row');if(!row)return;
  if(e.target.matches('.hq-ad-x,.hq-ad-y'))preview(row);
 });
 root.addEventListener('change',e=>{
  const row=e.target.closest('.hq-ad-editor-row');if(!row)return;
  if(e.target.matches('.hq-ad-preset')){
   const option=e.target.selectedOptions[0];if(!row.querySelector('.hq-ad-upload').value)
    row.querySelector('.hq-ad-preview-image').src=option?.dataset.image||'';
  }
  if(e.target.matches('.hq-ad-upload')){
   const ids=e.target.value;
   if(ids) {
    // Await the media library's own thumbnail/preview asynchronously; never
    // fabricate an uploaded_asset URL client-side.
    const preview=row.querySelector('.file-preview img');
    if(preview)row.querySelector('.hq-ad-preview-image').src=preview.src;
   }else {
    const selected=row.querySelector('.hq-ad-preset').selectedOptions[0];
    row.querySelector('.hq-ad-preview-image').src=selected?.dataset.image||'';
   }
  }
 });
 // The shared AIZ media uploader populates previews asynchronously. Observe
 // the uploader's preview container so the crop frame follows the selected image.
 root.querySelectorAll('.hq-ad-editor-row').forEach(row=>{
  const preview=row.querySelector('.file-preview');
  if(!preview)return;
  new MutationObserver(()=>{
   const image=preview.querySelector('img');
   if(image&&row.querySelector('.hq-ad-upload').value){
    row.querySelector('.hq-ad-preview-image').src=image.src;
   }
  }).observe(preview,{childList:true,subtree:true,attributes:true,attributeFilter:['src']});
 });
 root.querySelectorAll('.hq-ad-admin-section').forEach(sync);
})();
</script>
@endsection
