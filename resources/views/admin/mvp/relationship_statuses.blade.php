@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('Relationship Status Moderation') }}</h1>
</div>
<div class="card">
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Members') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Date') }}</th>
                    <th>{{ translate('Notes') }}</th>
                    <th class="text-right">{{ translate('Moderation') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statuses as $key => $item)
                    <tr>
                        <td>{{ ($key + 1) + ($statuses->currentPage() - 1) * $statuses->perPage() }}</td>
                        <td>{{ $item->user?->first_name }} {{ $item->user?->last_name }} & {{ $item->partner?->first_name }} {{ $item->partner?->last_name }}</td>
                        <td>{{ ucfirst($item->status) }}</td>
                        <td>{{ optional($item->status_date)->format('Y-m-d') }}</td>
                        <td>{{ Str::limit($item->notes, 80) }}</td>
                        <td class="text-right">
                            <form action="{{ route('admin.mvp.relationship_statuses.update', $item->id) }}" method="POST" class="d-inline-flex">
                                @csrf
                                <select name="moderation_status" class="form-control form-control-sm mr-2">
                                    @foreach (['pending', 'approved', 'rejected'] as $status)
                                        <option value="{{ $status }}" @selected($item->moderation_status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">{{ $statuses->links() }}</div>
    </div>
</div>
@endsection
