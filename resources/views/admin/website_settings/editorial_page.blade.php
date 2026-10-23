@extends('admin.layouts.app')
@section('content')
<style>
.hqcms-shell{max-width:1200px;margin:0 auto 50px}.hqcms-head{display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap;align-items:center;margin:20px 0}.hqcms-grid{display:grid;gap:18px}.hqcms-panel{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px #22001808;border:1px solid #eee8ee}.hqcms-panel h3{font-weight:600;color:#8d0d48!important;font-size:17px}.hqcms-panel h4{font-size:14px;margin:15px 0;color:#8d0d48!important}.hqcms-fields{display:grid;gap:12px}.hqcms-field{min-width:0}.hqcms-field label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:#4d3443}.hqcms-field input,.hqcms-field textarea{width:100%;border:1px solid #e0d4dc;border-radius:8px;background:#faf9fb;padding:9px 11px;font-size:13px}.hqcms-field textarea{min-height:95px;resize:vertical}.hqcms-nested{background:#faf7fb;border:1px solid #eee5ef;border-radius:12px;padding:14px;margin-top:14px}.hqcms-repeat{display:grid;gap:10px}.hqcms-repeat-item{padding:14px;border:1px solid #e7dfe8;border-radius:12px;background:white}.hqcms-item-controls{display:flex;justify-content:flex-end;gap:5px;margin-bottom:10px;flex-wrap:wrap}.hqcms-btn{border:1px solid #e8cad8;background:#fff;color:#981051;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:11px}.hqcms-btn.primary{background:#a30e51;color:white}.hqcms-btn:disabled{opacity:.4}.hqcms-toolbar{position:sticky;top:0;padding:13px 0;background:#f9f6fb;z-index:10;display:flex;justify-content:space-between;gap:10px;align-items:center}.hqcms-media-hint{font-size:11px;color:#695966}.hqcms-fields .hqcms-image{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.hqcms-image input{max-width:110px}.hqcms-image img{width:70px;height:55px;object-fit:cover;border-radius:7px}.hqcms-search{min-width:220px;border:1px solid #eadde6;border-radius:7px;padding:8px}.hqcms-hidden{display:none!important}@media(max-width:650px){.hqcms-toolbar{flex-wrap:wrap}.hqcms-panel{padding:13px}}
</style>
<div class="hqcms-shell">
<div class="hqcms-head"><div><h3>Manage {{ ['about'=>'About Us','team'=>'Our Team','privacy'=>'Privacy Policy','terms'=>'Terms & Conditions'][$page] }}</h3><p>Every heading, paragraph, button, link, list, card and label on this page is editable here. Add, reorder and remove repeatable items.</p></div>
<a href="{{ route('editorial.'.$page) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">Open live page <i class="las la-external-link-alt"></i></a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form action="{{ route('website.editorial.update',$page) }}" method="POST" id="hqcms-form">
@csrf
<input type="hidden" name="payload" id="hqcms-payload">
<div class="hqcms-toolbar"><input class="hqcms-search" type="search" placeholder="Search fields or sections..." id="hqcms-search" aria-label="Search editable fields"><span class="hqcms-media-hint">For image IDs, upload media under Uploaded Files first.</span><button class="btn btn-primary" type="submit">Save All Changes</button></div>
<div id="hqcms-editor" class="hqcms-grid"></div>
<div class="text-right mt-3"><button class="btn btn-primary" type="submit">Save All Changes</button></div>
</form>
</div>
<script type="application/json" id="hqcms-initial">@json($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="hqcms-schema">@json(\App\Http\Controllers\HamqadamEditorialController::defaults($page), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script src="{{ static_asset('assets/js/hamqadam-editorial-admin.js') }}?v=20261023" defer></script>
@endsection