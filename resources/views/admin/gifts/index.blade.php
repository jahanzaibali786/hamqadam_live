@extends('admin.layouts.app')
@section('content')
@php
    // Seeded gifts reference files bundled inside the mobile app ("assets/..."),
    // so the admin can only preview uploaded media.
    $mediaUrl = fn (?string $p) => \App\Models\Gift::publicUrl($p);
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Gifts') }}</h1></div>
        <div class="col-md-6 text-md-right">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#gift-modal" onclick="giftForm()">
                <i class="las la-plus"></i> {{ translate('Add New Gift') }}
            </button>
        </div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Gift Catalog') }}</h5></div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Thumbnail') }}</th>
                    <th>{{ translate('Animation') }}</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('Coins') }}</th>
                    <th>{{ translate('Sends') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($gifts as $gift)
                @php($type = $gift->resolvedMediaType())
                @php($anim = $mediaUrl($gift->animated_asset))
                <tr>
                    <td>{{ $gift->sort_order }}</td>
                    <td>
                        @if($thumb = $mediaUrl($gift->thumbnail))
                            <img src="{{ $thumb }}" alt="" style="width:56px;height:56px;object-fit:contain;border-radius:10px;background:#faf5f8">
                        @else
                            <span class="badge badge-inline badge-secondary">{{ translate('App bundled') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="gift-preview" data-type="{{ $type }}" data-src="{{ $anim }}" style="width:64px;height:64px">
                            @if($anim && $type === 'gif')
                                <img src="{{ $anim }}" alt="" style="width:64px;height:64px;object-fit:contain">
                            @elseif($anim && $type === 'mp4')
                                <video src="{{ $anim }}" autoplay loop muted playsinline style="width:64px;height:64px;object-fit:contain"></video>
                            @elseif(! $anim)
                                <span class="text-muted small">—</span>
                            @endif
                        </div>
                        <span class="badge badge-inline badge-soft-info mt-1 text-uppercase">{{ $type }}</span>
                    </td>
                    <td>{{ $gift->name }}<div class="small text-muted">{{ $gift->category }}</div></td>
                    <td><i class="las la-coins text-warning"></i> {{ $gift->coins }}</td>
                    <td>{{ $gift->transactions()->count() }}</td>
                    <td>
                        @if($gift->is_active)
                            <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ translate('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <button type="button" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}"
                            data-toggle="modal" data-target="#gift-modal"
                            onclick="giftForm({{ json_encode($gift->only('id', 'name', 'coins', 'category', 'sort_order', 'is_active')) }})">
                            <i class="las la-edit"></i>
                        </button>
                        <form action="{{ route('admin.gifts.deactivate', $gift) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-soft-{{ $gift->is_active ? 'danger' : 'success' }} btn-icon btn-circle btn-sm"
                                title="{{ $gift->is_active ? translate('Deactivate') : translate('Activate') }}">
                                <i class="las la-{{ $gift->is_active ? 'ban' : 'check' }}"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ translate('No gifts yet. Add the first one.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Recent Gift Transactions') }}</h5></div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead><tr><th>{{ translate('Gift') }}</th><th>{{ translate('Sender') }}</th><th>{{ translate('Receiver') }}</th><th>{{ translate('Coins') }}</th><th>{{ translate('Date') }}</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr><td>{{ $t->gift?->name }}</td><td>{{ $t->sender?->first_name }} #{{ $t->sender_id }}</td><td>{{ $t->receiver?->first_name }} #{{ $t->receiver_id }}</td><td>{{ $t->coins }}</td><td>{{ $t->created_at?->format('d M Y') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">{{ translate('No gift transactions.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection

@section('modal')
<div class="modal fade" id="gift-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ route('admin.gifts.save') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="gift_id" id="gift-id">
            <div class="modal-header"><h5 class="modal-title h6" id="gift-modal-title">{{ translate('Add New Gift') }}</h5><button type="button" class="close" data-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="gift-name">{{ translate('Gift Name') }}</label>
                    <input class="form-control" id="gift-name" name="name" maxlength="60" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="gift-coins">{{ translate('Coins Consumed') }}</label>
                        <input class="form-control" id="gift-coins" name="coins" type="number" min="1" required>
                    </div>
                    <div class="form-group col-6">
                        <label for="gift-sort">{{ translate('Sort Order') }}</label>
                        <input class="form-control" id="gift-sort" name="sort_order" type="number" min="0" placeholder="{{ translate('Auto') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="gift-category">{{ translate('Category') }}</label>
                    <input class="form-control" id="gift-category" name="category" maxlength="40" placeholder="general">
                </div>
                <div class="form-group">
                    <label for="gift-thumb">{{ translate('Thumbnail') }} <small class="text-muted">(PNG / JPG / WEBP / GIF, max 2MB)</small></label>
                    <input class="form-control" id="gift-thumb" name="thumbnail_file" type="file" accept=".png,.jpg,.jpeg,.webp,.gif">
                </div>
                <div class="form-group">
                    <label for="gift-anim">{{ translate('Animation') }} <small class="text-muted">(GIF / SVGA / MP4 / Lottie JSON, max 20MB)</small></label>
                    <input class="form-control" id="gift-anim" name="animation_file" type="file" accept=".gif,.svga,.mp4,.json">
                    <small class="form-text text-muted d-none" id="gift-file-hint">{{ translate('Leave empty to keep the current file.') }}</small>
                </div>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" name="is_active" value="1" id="gift-active" checked><span></span>
                </label>
                <span class="ml-1">{{ translate('Active') }}</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/svgaplayerweb@2.3.2/build/svga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lottie-web@5.12.2/build/player/lottie.min.js"></script>
<script>
    function giftForm(g) {
        const edit = !!g;
        document.getElementById('gift-modal-title').textContent = edit ? '{{ translate('Edit Gift') }}' : '{{ translate('Add New Gift') }}';
        document.getElementById('gift-id').value = edit ? g.id : '';
        document.getElementById('gift-name').value = edit ? g.name : '';
        document.getElementById('gift-coins').value = edit ? g.coins : '';
        document.getElementById('gift-sort').value = edit ? g.sort_order : '';
        document.getElementById('gift-category').value = edit ? g.category : '';
        document.getElementById('gift-active').checked = edit ? !!g.is_active : true;
        // Files are required only when creating.
        document.getElementById('gift-thumb').required = !edit;
        document.getElementById('gift-anim').required = !edit;
        document.getElementById('gift-file-hint').classList.toggle('d-none', !edit);
    }

    document.querySelectorAll('.gift-preview').forEach(function (el) {
        const src = el.dataset.src;
        if (!src) return;
        if (el.dataset.type === 'svga' && window.SVGA) {
            const player = new SVGA.Player(el), parser = new SVGA.Parser(el);
            parser.load(src, function (item) { player.setVideoItem(item); player.startAnimation(); });
        } else if (el.dataset.type === 'lottie' && window.lottie) {
            lottie.loadAnimation({ container: el, renderer: 'svg', loop: true, autoplay: true, path: src });
        }
    });
</script>
@endsection
