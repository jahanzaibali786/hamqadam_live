@php
    $targetId=(int)$person->id;
    $status=$matchActions['status'];
    $thread=$matchActions['thread'];
@endphp
<div class="hm-match-controls hm-appreciation-card-actions">
    <a class="hm-match-send" href="{{ $detailUrl }}"><i class="las la-eye"></i> View Profile</a>
    <a class="hm-match-send" href="{{ route('member.match.why',$targetId) }}"><i class="las la-magic"></i> Why This Match?</a>
    <a class="hm-match-send" href="{{ route('member.workflow.show',$targetId) }}"><i class="las la-heart"></i> Send Interest</a>
    <a class="hm-match-send" href="{{ route('member.workflow.show',$targetId) }}"><i class="las la-envelope-open"></i> Send Proposal</a>
    <a class="hm-match-send" href="{{ route('member.gifts.compose',$targetId) }}"><i class="las la-gift"></i> Send Gift</a>
    @if($status===1)
        <a class="hm-match-send" href="{{ $thread ? route('chat_view',$thread->id) : route('all.messages') }}"><i class="las la-comment-dots"></i> Chat</a>
    @endif
    @include('frontend.member.partials.profile_appreciations',['person'=>$person])
</div>
