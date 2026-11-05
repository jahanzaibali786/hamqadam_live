@php
    $targetId = (int) $person->id;
    $interest = $matchActions['interest'];
    $status = $matchActions['status'];
    $thread = $matchActions['thread'];
    $superLiked = \App\Models\ProfileSuperLike::where('sender_id', auth()->id())->where('recipient_id', $targetId)->exists();
    $shortlisted = \App\Models\Shortlist::where('user_id', $targetId)->where('shortlisted_by', auth()->id())->exists();
@endphp
<div class="hm-match-menu">
    <button type="button" class="hm-match-menu-toggle" aria-haspopup="true"
        aria-expanded="false" aria-label="More actions for {{ $matchActions['display_name'] }}"
        title="More actions">
        <i class="las la-ellipsis-v" aria-hidden="true"></i>
    </button>
    <div class="hm-match-menu-panel" role="menu"
        data-member-name="{{ trim($person->first_name.' '.$person->last_name) }}"
        aria-label="Actions for {{ $matchActions['display_name'] }}" hidden>
        <button type="button" role="menuitem"
            class="hm-match-menu-item hm-appreciate-super {{ $superLiked ? 'is-active' : '' }}"
            data-super-url="{{ route('member.profile.superlike', $targetId) }}"
            @disabled($superLiked)
            aria-pressed="{{ $superLiked ? 'true' : 'false' }}"
            aria-label="Super Like {{ $person->first_name }}">
            <i class="lar la-star" aria-hidden="true"></i><span>{{ $superLiked ? 'Super Liked' : 'Super Like' }}</span>
        </button>
        <button type="button" role="menuitem"
            class="hm-match-menu-item hm-appreciate-shortlist {{ $shortlisted ? 'is-active' : '' }}"
            data-shortlist-id="{{ $targetId }}"
            data-shortlist-add-url="{{ route('member.add_to_shortlist') }}"
            data-shortlist-remove-url="{{ route('member.remove_from_shortlist') }}"
            aria-pressed="{{ $shortlisted ? 'true' : 'false' }}"
            title="{{ $shortlisted ? 'Remove from Shortlist' : 'Shortlist' }}"
            aria-label="{{ $shortlisted ? 'Remove from Shortlist' : 'Shortlist' }} {{ $person->first_name }}">
            <i class="{{ $shortlisted ? 'las' : 'lar' }} la-bookmark" aria-hidden="true"></i><span>{{ $shortlisted ? 'Remove from Shortlist' : 'Add to Shortlist' }}</span>
        </button>
        @if (!$interest)
            <a href="{{ route('member.workflow.show', $targetId) }}" class="hm-match-menu-item" role="menuitem"><i class="las la-heart"></i> Send Interest</a>
        @endif
        <a class="hm-match-menu-item" role="menuitem" href="{{ route('member.workflow.show', $targetId) }}"><i class="las la-envelope-open"></i> Interest &amp; Proposal Workflow</a>
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
