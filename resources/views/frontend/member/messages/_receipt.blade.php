@php
    $receiptState = $chat->seen || $chat->read_at ? 'read' : ($chat->delivered_at ? 'delivered' : 'sent');
    $receiptLabel = ['read' => 'Seen', 'delivered' => 'Delivered', 'sent' => 'Sent'][$receiptState];
@endphp
<span class="hm-msg-status" data-message-id="{{ $chat->id }}" data-status="{{ $receiptState }}" title="{{ $receiptLabel }}" aria-label="{{ $receiptLabel }}">
    <svg viewBox="0 0 23 17" aria-hidden="true" focusable="false">
        @if($receiptState === 'sent')
            <path d="M2 9l5 5L17 3"/>
        @else
            <path d="M1 9l5 5L16 3M8 9l5 5L23 3"/>
        @endif
    </svg><span class="hm-msg-status-word">{{ $receiptLabel }}</span>
</span>
