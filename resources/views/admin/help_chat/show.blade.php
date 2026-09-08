@extends('admin.layouts.app')

@php
    // The raw Pusher config the page's realtime shim needs. Read the same way
    // the frontend layout does, so one admin setting drives both.
    $pusherKey = get_setting('pusher_app_key', env('PUSHER_APP_KEY'));
    $pusherCluster = get_setting('pusher_app_cluster', env('PUSHER_APP_CLUSTER'));
    $realtimeEnabled = get_setting('chat_realtime_enabled') == 1 && $pusherKey !== '';
@endphp

@section('content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">
                    {{ translate('Help Center Chat') }} —
                    {{ trim(($thread->user->first_name ?? '') . ' ' . ($thread->user->last_name ?? '')) ?: translate('Member') }}
                </h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('admin.help-chat.index') }}" class="btn btn-soft-secondary btn-sm">
                    <i class="las la-arrow-left"></i> {{ translate('All conversations') }}
                </a>
                <form method="POST" action="{{ route('admin.help-chat.status', $thread->id) }}" class="d-inline"
                    onsubmit="return confirm('{{ translate('Are you sure?') }}')">
                    @csrf
                    <input type="hidden" name="status"
                        value="{{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? 'closed' : 'open' }}">
                    <button type="submit"
                        class="btn btn-sm {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? 'btn-soft-warning' : 'btn-soft-success' }}">
                        {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? translate('Close conversation') : translate('Reopen conversation') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">
                        <span class="badge badge-inline {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? 'badge-warning' : 'badge-success' }}">
                            {{ $thread->status === \App\Models\HelpChatThread::STATUS_OPEN ? translate('Open') : translate('Closed') }}
                        </span>
                        {{ translate('Member') }}: {{ $thread->user->email }} ({{ $thread->user->code }})
                    </h5>
                </div>

                <div class="card-body" style="height: 480px; overflow-y: auto;" id="help-chat-scroll">
                    <div id="help-chat-messages" data-thread-id="{{ $thread->id }}">
                        @forelse ($messages as $message)
                            @include('admin.help_chat.partials.message', ['message' => $message])
                        @empty
                            <p class="text-center text-muted py-4">{{ translate('No messages yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card-footer">
                    @if ($thread->status === \App\Models\HelpChatThread::STATUS_CLOSED)
                        <p class="text-muted mb-0 text-center">
                            {{ translate('This conversation is closed. Reopen it to reply.') }}
                        </p>
                    @else
                        <form id="help-chat-reply-form" action="{{ route('admin.help-chat.reply', $thread->id) }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-2">
                                <textarea class="form-control" rows="3" name="message" id="help-chat-reply-input"
                                    placeholder="{{ translate('Write your reply to the member…') }}"
                                    style="resize: none;"></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="custom-file w-auto" style="max-width: 260px;">
                                    <input type="file" class="custom-file-input" name="attachments[]"
                                        id="help-chat-attachments" multiple
                                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                                    <label class="custom-file-label text-truncate" for="help-chat-attachments">
                                        {{ translate('Attach files (optional)') }}
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary" id="help-chat-reply-btn">
                                    <i class="las la-paper-plane"></i> {{ translate('Send reply') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        (function () {
            var threadId = document.getElementById('help-chat-messages').dataset.threadId;
            var scrollBox = document.getElementById('help-chat-scroll');

            function scrollToBottom() {
                scrollBox.scrollTop = scrollBox.scrollHeight;
            }
            scrollToBottom();

            // ── Optimistic render + POST ────────────────────────────────────
            // The reply is drawn the moment Send is clicked and reconciled
            // when the POST returns, so the panel feels instant on a slow
            // connection. A failure removes the bubble and says so — the text
            // is put back in the input so nothing is lost.
            var form = document.getElementById('help-chat-reply-form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var input = document.getElementById('help-chat-reply-input');
                    var text = input.value.trim();
                    var files = document.getElementById('help-chat-attachments').files;
                    if (!text && files.length === 0) return;

                    var btn = document.getElementById('help-chat-reply-btn');
                    btn.disabled = true;

                    var fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    if (text) fd.append('message', text);
                    for (var i = 0; i < files.length; i++) fd.append('attachments[]', files[i]);

                    fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(function (r) {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(function (data) {
                            if (data && data.message_html) {
                                document.getElementById('help-chat-messages')
                                    .insertAdjacentHTML('beforeend', data.message_html);
                                scrollToBottom();
                            }
                            input.value = '';
                            document.getElementById('help-chat-attachments').value = '';
                        })
                        .catch(function () {
                            alert('{{ translate('Could not send the reply. Please try again.') }}');
                        })
                        .finally(function () {
                            btn.disabled = false;
                        });
                });
            }

            // ── Realtime (Pusher) ───────────────────────────────────────────
            @if ($realtimeEnabled)
                if (typeof window.Pusher === 'undefined') return;

                var pusher = new window.Pusher('{{ $pusherKey }}', {
                    cluster: '{{ $pusherCluster }}',
                    forceTLS: true,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '{{ url("/broadcasting/auth") }}',
                    authTransport: 'ajax',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }
                });

                var channel = pusher.subscribe('private-help-chat.' + threadId);

                channel.bind('pusher:subscription_error', function (err) {
                    console.warn('Help chat realtime auth failed; new messages will appear on reload.', err);
                });

                // The member's new message. Rendered from the broadcast payload
                // itself — no reload, no polling.
                channel.bind('help-message-sent', function (payload) {
                    if (!payload || payload.from_admin) return;
                    fetch('{{ route('admin.help-chat.message_html', $thread->id) }}?message_id=' + (payload.id || 0), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(function (r) {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(function (data) {
                            if (data && data.message_html) {
                                document.getElementById('help-chat-messages')
                                    .insertAdjacentHTML('beforeend', data.message_html);
                                scrollToBottom();
                            }
                        })
                        .catch(function () {
                            // A missed realtime message still shows on reload.
                        });
                });
            @endif
        })();
    </script>
@endsection
