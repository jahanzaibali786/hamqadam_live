{{-- One message bubble in the admin Help Center chat.
    Included by show.blade.php and, over HTTP, by the controller's
    messageHtml endpoint — which is how a message arriving over Pusher gets
    rendered with exactly the same markup as a page reload would give. --}}
@php
    $mine = in_array($message->sender->user_type ?? '', ['admin', 'staff', 'subadmin'], true);
@endphp
<div class="help-chat-msg d-flex mb-3 {{ $mine ? 'justify-content-end' : '' }}" data-message-id="{{ $message->id }}">
    <div style="max-width: 75%;">
        <div class="p-2 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light border' }}">
            @if (trim((string) $message->message) !== '')
                <div style="white-space: pre-wrap; word-break: break-word;">{{ $message->message }}</div>
            @endif
            @if ($message->attachment)
                <div class="mt-1">
                    @foreach ($message->attachmentIds() as $attachmentId)
                        <a href="{{ route('download_attachment', $attachmentId) }}" target="_blank"
                            class="d-inline-block mr-1 {{ $mine ? 'text-white' : '' }}">
                            <i class="las la-paperclip"></i> {{ translate('Attachment') }} {{ $loop->iteration }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="text-muted mt-1" style="font-size: 11px;">
            {{ $mine ? translate('You') : ($message->sender ? trim(($message->sender->first_name ?? '') . ' ' . ($message->sender->last_name ?? '')) : translate('Member')) }}
            · {{ $message->created_at->format('d M Y, h:i A') }}
        </div>
    </div>
</div>
