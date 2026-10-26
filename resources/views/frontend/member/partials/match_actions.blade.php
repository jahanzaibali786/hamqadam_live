@php
    $targetId = (int) $person->id;
    $interest = $matchActions['interest'];
    $status = $matchActions['status'];
    $thread = $matchActions['thread'];
@endphp
<div class="hm-match-controls">
    <a class="hm-match-send" href="{{ route('member.workflow.show', $targetId) }}"><i class="las la-heart"></i> Send Interest / Proposal</a>
    @if (!$interest)
        <form action="{{ route('member.match.propose', $targetId) }}" method="POST" class="hm-match-send-form">
            @csrf
            <button class="hm-match-send" type="submit"><i class="las la-paper-plane"></i> Send Proposal / Interest</button>
        </form>
    @elseif ($status === 1)
        <a class="hm-match-send" href="{{ $thread ? route('chat_view', $thread->id) : route('all.messages') }}"><i class="las la-comment-dots"></i> Accepted · Chat</a>
    @else
        <span class="hm-match-status">{{ $status === 0 ? 'Proposal Sent' : 'Proposal ' . ($interest->status?->label() ?? 'Closed') }}</span>
    @endif
</div>
