<form action="{{ route('members.package_do_update', $member_id) }}" method="POST">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Upgrade Package')}}</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="row gutters-10">
            @foreach($packages as $package)
            {{-- col-6 on phones / small laptops, col-md-3 from 768px up —
                 the old col-4 squeezed three columns into a 500px modal and
                 the package cards became unreadable. --}}
            <div class="col-6 col-md-3">
                <label class="aiz-megabox d-block mb-3">
                    <input value="{{ $package->id }}" type="radio" name="package_id" required>
                    <span class="d-block p-3 aiz-megabox-elem">
                        @if(uploaded_asset($package->image) != null)
                            <img src="{{ uploaded_asset($package->image)}}" class="img-fluid mb-2" style="max-height:64px">
                        @endif
                        <span class="d-block text-center">
                            <span class="d-block fw-600 fs-15">{{ $package->name }}</span>
                            <span class="d-block text-muted fs-10">
                                {{ $package->express_interest }} interests · {{ $package->validity }}d
                            </span>
                        </span>
                    </span>
                </label>
            </div>
            @endforeach
        </div>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
    <button type="submit" class="btn btn-success">{{translate('Submit')}}</button>
</div>
</form>
