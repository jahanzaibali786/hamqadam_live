<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-0 h6">{{ $config['title'] }}</h5>
                <small class="text-muted">{{ translate('Create, edit and delete managed dropdown values.') }}</small>
            </div>
            <form action="{{ route('website.dropdown_data.index') }}" method="GET" class="mt-2 mt-md-0">
                <input type="hidden" name="tab" value="{{ $config['group'] }}">
                <input type="search" class="form-control form-control-sm" style="min-width: 240px;" name="search_{{ $type }}" value="{{ $searchValue }}" placeholder="{{ translate('Search') }}">
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-5">
                <form action="{{ route('website.dropdown_data.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    @include('admin.website_settings.dropdown_data._fields', [
                        'type' => $type,
                        'config' => $config,
                        'record' => null,
                        'lookups' => $lookups,
                    ])
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-7">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                @foreach($config['columns'] as $column)
                                    <th>{{ $column['label'] }}</th>
                                @endforeach
                                <th class="text-right" style="width: 120px;">{{ translate('Options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $key => $record)
                                <tr>
                                    <td>{{ ($key + 1) + ($records->currentPage() - 1) * $records->perPage() }}</td>
                                    @foreach($config['columns'] as $column)
                                        <td>{{ $column['value']($record, $lookups) }}</td>
                                    @endforeach
                                    <td class="text-right">
                                        <a href="javascript:void(0);" onclick="dropdown_data_modal('{{ route('website.dropdown_data.edit', [$type, encrypt($record->id)]) }}')" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" data-href="{{ route('website.dropdown_data.destroy', [$type, $record->id]) }}" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($config['columns']) + 2 }}" class="text-center text-muted py-4">{{ translate('No records found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="aiz-pagination">
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
