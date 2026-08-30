<form action="{{ route('website.dropdown_data.update', [$type, $record->id]) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="modal-header">
        <h5 class="modal-title h6">{{ translate('Edit') }} {{ $config['title'] }}</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        @include('admin.website_settings.dropdown_data._fields', [
            'type' => $type,
            'config' => $config,
            'record' => $record,
            'lookups' => $lookups,
        ])
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
        <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
    </div>
</form>
