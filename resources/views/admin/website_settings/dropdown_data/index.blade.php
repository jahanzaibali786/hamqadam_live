@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Dropdowns Data') }}</h1>
            <p class="mb-0 text-muted">{{ translate('Manage registration dropdown data from one place.') }}</p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body pb-0">
        <ul class="nav nav-tabs nav-fill border-0" role="tablist">
            @foreach($groups as $key => $label)
                <li class="nav-item">
                    <a class="nav-link @if($activeTab === $key) active @endif" href="{{ route('website.dropdown_data.index', ['tab' => $key]) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

@foreach($groupItems as $type => $config)
    @include('admin.website_settings.dropdown_data._card', [
        'type' => $type,
        'config' => $config,
        'records' => $records[$type] ?? collect(),
        'searchValue' => $searches[$type] ?? null,
        'lookups' => $lookups,
    ])
@endforeach
@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    function dropdown_data_modal(url) {
        $.get(url, function (data) {
            $('.create_edit_modal_content').html(data);
            $('.create_edit_modal').modal('show');
        });
    }
</script>
@endsection
