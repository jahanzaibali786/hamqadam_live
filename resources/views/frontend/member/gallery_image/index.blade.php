@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-gallery-v2">
<header class="hm-gallery-head"><div><span class="hm-gallery-icon"><i class="las la-images"></i></span><div><h1>My Photo Gallery</h1><p>Manage your gallery pictures and share your personality.</p></div></div><a class="hm-pink-action" href="{{ route('gallery-image.create') }}"><i class="las la-plus"></i> Add New Photo</a></header>
<div class="hm-gallery-stats"><div><i class="las la-images"></i><span>My Photos<strong>{{ $gallery_images->total() }}</strong></span></div><div><i class="las la-camera"></i><span>Remaining Uploads<strong>{{ get_remaining_package_value(auth()->id(),'remaining_photo_gallery') }}</strong></span></div><a href="{{ route('member.reference.packages') }}"><i class="las la-crown"></i> Explore Plans <i class="las la-arrow-right"></i></a></div>
<section class="hm-gallery-panel"><div class="hm-gallery-title"><h2>Gallery Photos</h2><small>Your existing uploaded images</small></div><div class="hm-gallery-grid">
@forelse($gallery_images as $gallery_image)
<article class="hm-gallery-item"><a target="_blank" rel="noopener" href="{{ uploaded_asset($gallery_image->image) }}"><img loading="lazy" src="{{ uploaded_asset($gallery_image->image) }}" alt="Gallery photo"></a><div><a href="{{ uploaded_asset($gallery_image->image) }}" target="_blank" rel="noopener"><i class="las la-expand"></i> View</a><button type="button" data-gallery-delete="{{ route('gallery_image.destroy',$gallery_image->id) }}"><i class="las la-trash-alt"></i> Remove</button></div></article>
@empty<div class="hm-gallery-empty"><i class="las la-image"></i><h3>No gallery photos yet</h3><p>Upload your first image to personalize your gallery.</p><a class="hm-pink-action" href="{{ route('gallery-image.create') }}">Upload Photo</a></div>@endforelse
</div><div class="mt-3">{{ $gallery_images->links() }}</div></section>
</div>
@endsection
@section('modal')
<div class="modal fade" id="image_delete_modal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h4>Remove Gallery Photo?</h4><button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button></div><div class="modal-body">This will delete the selected photo. Your gallery quota will not be refunded.</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><a id="delete_link" class="btn btn-danger" href="#">Delete Photo</a></div></div></div></div>
@endsection
@section('script')
<script>document.querySelectorAll('[data-gallery-delete]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('delete_link').href=button.dataset.galleryDelete;$('#image_delete_modal').modal('show');}));</script>
@endsection