@php
    $targetId = (int) $person->id;
    $status = $matchActions['status'];
    $thread = $matchActions['thread'];
@endphp
{{-- One reusable action layout for Dashboard, Matched Profiles and member discovery.
     Keep every existing destination and the chat-acceptance gate. --}}
<div class="hm-match-controls hm-appreciation-card-actions" aria-label="Actions for {{ $person->first_name }}">
    <a class="hm-match-send hm-card-action--profile" href="{{ $detailUrl }}"><i class="las la-eye" aria-hidden="true"></i> View Profile</a>
    <a class="hm-match-send hm-card-action--why" href="{{ route('member.match.why', $targetId) }}"><i class="las la-magic" aria-hidden="true"></i> Why This Match?</a>
    <a class="hm-match-send hm-card-action--interest" href="{{ route('member.workflow.show', $targetId) }}"><i class="las la-heart" aria-hidden="true"></i> Send Interest</a>
    <a class="hm-match-send hm-card-action--proposal" href="{{ route('member.workflow.show', $targetId) }}"><i class="las la-envelope-open" aria-hidden="true"></i> Send Proposal</a>
    <a class="hm-match-send hm-card-action--gift" href="{{ route('member.gifts.compose', $targetId) }}"><i class="las la-gift" aria-hidden="true"></i> Send Gift</a>
    @if ($status === 1)
        <a class="hm-match-send hm-card-action--chat" href="{{ $thread ? route('chat_view', $thread->id) : route('all.messages') }}"><i class="las la-comment-dots" aria-hidden="true"></i> Chat</a>
    @endif
    @include('frontend.member.partials.profile_appreciations', ['person' => $person])
</div>
