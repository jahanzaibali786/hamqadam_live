@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <h1 class="h3">{{ translate('Proposal Meetings') }}</h1>
</div>
<div class="card">
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Proposal') }}</th>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Scheduled') }}</th>
                    <th>{{ translate('Chaperone') }}</th>
                    <th class="text-right">{{ translate('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meetings as $key => $meeting)
                    <tr>
                        <td>{{ ($key + 1) + ($meetings->currentPage() - 1) * $meetings->perPage() }}</td>
                        <td>{{ $meeting->proposal?->sender?->first_name }} {{ $meeting->proposal?->sender?->last_name }} / {{ $meeting->proposal?->recipient?->first_name }} {{ $meeting->proposal?->recipient?->last_name }}</td>
                        <td>{{ ucfirst($meeting->meeting_type) }}</td>
                        <td>{{ optional($meeting->scheduled_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $meeting->chaperone_mode ? translate('Enabled') : translate('No') }}</td>
                        <td class="text-right">
                            <form action="{{ route('admin.mvp.proposal_meetings.update', $meeting->id) }}" method="POST" class="d-inline-flex">
                                @csrf
                                <select name="status" class="form-control form-control-sm mr-2">
                                    @foreach (['scheduled', 'completed', 'cancelled', 'rescheduled'] as $status)
                                        <option value="{{ $status }}" @selected($meeting->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">{{ $meetings->links() }}</div>
    </div>
</div>
@endsection
