@extends('admin.layouts.app')

@section('content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Help Center Chats') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <span class="badge badge-inline badge-warning">{{ translate('Open') }}: {{ $openCount }}</span>
                <span class="badge badge-inline badge-success">{{ translate('Closed') }}: {{ $closedCount }}</span>
                @if ($totalUnread > 0)
                    <span class="badge badge-inline badge-danger">{{ translate('Unread') }}: {{ $totalUnread }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header row gutters-5">
                    <div class="col text-center text-md-left">
                        <h5 class="mb-md-0 h6">{{ translate('Members with an issue') }}</h5>
                    </div>
                    <div class="col-auto">
                        <form method="GET" action="{{ route('admin.help-chat.index') }}" class="form-inline">
                            <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="open" {{ $status === 'open' ? 'selected' : '' }}>{{ translate('Open') }}</option>
                                <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>{{ translate('Closed') }}</option>
                            </select>
                            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm mr-2"
                                placeholder="{{ translate('Search member') }}">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Search') }}</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Member') }}</th>
                                <th data-breakpoints="md">{{ translate('Code') }}</th>
                                <th data-breakpoints="md">{{ translate('Last message') }}</th>
                                <th data-breakpoints="md">{{ translate('Last activity') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th class="text-right" width="10%">{{ translate('Options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($threads as $thread)
                                <tr>
                                    <td>{{ $loop->index + 1 + ($threads->currentPage() - 1) * $threads->perPage() }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ uploaded_asset($thread->user->photo) }}"
                                                class="img-fluid rounded-circle mr-2" width="32" height="32"
                                                onerror="this.src='{{ static_asset('assets/img/placeholders/user.jpg') }}'">
                                            <div>
                                                <a href="{{ route('admin.help-chat.show', $thread->id) }}"
                                                    class="text-reset font-weight-bold">
                                                    {{ trim(($thread->user->first_name ?? '') . ' ' . ($thread->user->last_name ?? '')) ?: translate('Member') }}
                                                </a>
                                                @if ((int) $thread->admin_unread_count > 0)
                                                    <span class="badge badge-danger badge-inline ml-1">{{ $thread->admin_unread_count }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $thread->user->code }}</td>
                                    <td class="text-truncate" style="max-width: 260px;">
                                        @if ($thread->lastMessage)
                                            {{ \Illuminate\Support\Str::limit($thread->lastMessage->message ?: translate('📎 Attachment'), 60) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $thread->last_message_at ? \Carbon\Carbon::parse($thread->last_message_at)->diffForHumans() : '—' }}</td>
                                    <td>
                                        <span class="badge badge-inline {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? 'badge-warning' : 'badge-success' }}">
                                            {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? translate('Open') : translate('Closed') }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.help-chat.show', $thread->id) }}"
                                            class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Open chat') }}">
                                            <i class="las la-comments"></i>
                                        </a>
                                        <a href="{{ route('admin.help-chat.destroy', $thread->id) }}"
                                            class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                            title="{{ translate('Delete') }}">
                                            <i class="las la-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        {{ translate('No help conversations yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="aiz-pagination">
                        {{ $threads->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection
