@php
    $targetId = (int) $person->id;
    $interest = $matchActions['interest'];
    $status = $matchActions['status'];
    $thread = $matchActions['thread'];
@endphp
<div class="hm-match-menu">
    <button type="button" class="hm-match-menu-toggle" aria-haspopup="true"
        aria-expanded="false" aria-label="More actions for {{ $matchActions['display_name'] }}">
        <i class="las la-ellipsis-v" aria-hidden="true"></i>
    </button>
    <div class="hm-match-menu-panel" role="menu" aria-label="Actions for {{ $matchActions['display_name'] }}" hidden>
        @if (!$interest)
            <form method="POST" action="{{ route('member.match.propose', $targetId) }}">
                @csrf
                <button type="submit" class="hm-match-menu-item" role="menuitem"><i class="las la-heart"></i> Send Interest / Proposal</button>
            </form>
        @endif
        <a class="hm-match-menu-item" role="menuitem" href="{{ $detailUrl }}"><i class="las la-user"></i> View Profile</a>
        <a class="hm-match-menu-item" role="menuitem" href="{{ route('member.match.why', $targetId) }}"><i class="las la-magic"></i> Why This Match</a>
        @if ($status === 1)
            <a class="hm-match-menu-item" role="menuitem" href="{{ $thread ? route('chat_view', $thread->id) : route('all.messages') }}"><i class="las la-comments"></i> Chat</a>
        @else
            <span class="hm-match-menu-item is-disabled" aria-disabled="true"><i class="las la-lock"></i> Chat after acceptance</span>
        @endif
        <form action="{{ route('reportusers.store') }}" method="POST"
            onsubmit="var reason=prompt('Why are you reporting this member?'); if(!reason || !reason.trim()) return false; this.querySelector('[name=reason]').value=reason.trim(); return true;">
            @csrf
            <input type="hidden" name="member_id" value="{{ $targetId }}">
            <input type="hidden" name="reason" value="">
            <button type="submit" class="hm-match-menu-item" role="menuitem"><i class="las la-flag"></i> Report</button>
        </form>
    </div>
</div>
