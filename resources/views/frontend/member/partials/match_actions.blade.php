@php
 $viewerId=(int)auth()->id();$targetId=(int)$person->id;
 $interest=\App\Models\ExpressInterest::query()->where(function($q) use($viewerId,$targetId){
  $q->where(fn($a)=>$a->where('interested_by',$viewerId)->where('user_id',$targetId))
    ->orWhere(fn($a)=>$a->where('interested_by',$targetId)->where('user_id',$viewerId));
 })->latest()->first();
 $status=$interest?->status instanceof \BackedEnum ? (int)$interest->status->value : (int)($interest?->status??-1);
 $thread=$interest&&$status===1?\App\Models\ChatThread::query()->where(fn($q)=>$q->where('sender_user_id',$viewerId)->where('receiver_user_id',$targetId))->orWhere(fn($q)=>$q->where('sender_user_id',$targetId)->where('receiver_user_id',$viewerId))->first():null;
 $displayName=trim((string)($person->first_name??'').' '.(string)($person->last_name??''))?:'Member';
@endphp
<div class="hm-match-controls">
 @if(!$interest)
 <form action="{{ route('member.match.propose',$targetId) }}" method="POST" class="hm-match-send-form">@csrf<button class="hm-match-send" type="submit"><i class="las la-paper-plane"></i> Send Proposal / Interest</button></form>
 @elseif($status===1)
 <a class="hm-match-send" href="{{ $thread?route('chat_view',$thread->id):route('all.messages') }}"><i class="las la-comment-dots"></i> Accepted · Chat</a>
 @else
 <span class="hm-match-status">{{ $status===0?'Proposal Sent':'Proposal '.($interest->status?->label()??'Closed') }}</span>
 @endif
 <div class="dropdown hm-match-menu">
 <button type="button" class="hm-match-menu-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Actions for {{ $displayName }}"><i class="las la-ellipsis-v"></i></button>
 <div class="dropdown-menu dropdown-menu-right">
 @if(!$interest)<form action="{{ route('member.match.propose',$targetId) }}" method="POST">@csrf<button type="submit" class="dropdown-item"><i class="las la-heart"></i> Send Interest / Proposal</button></form>@endif
 <a class="dropdown-item" href="{{ $detailUrl }}"><i class="las la-user"></i> View Profile</a>
 <a class="dropdown-item" href="{{ route('member.match.why',$targetId) }}"><i class="las la-magic"></i> Why This Match</a>
 @if($status===1)<a class="dropdown-item" href="{{ $thread?route('chat_view',$thread->id):route('all.messages') }}"><i class="las la-comments"></i> Chat</a>@else<span class="dropdown-item text-muted" title="Chat becomes available after proposal acceptance"><i class="las la-lock"></i> Chat after acceptance</span>@endif
 <form action="{{ route('reportusers.store') }}" method="POST" onsubmit="var reason=prompt('Why are you reporting this member?');if(!reason||!reason.trim())return false;this.querySelector('[name=reason]').value=reason.trim();return true;">@csrf<input type="hidden" name="member_id" value="{{ $targetId }}"><input type="hidden" name="reason"><button type="submit" class="dropdown-item"><i class="las la-flag"></i> Report</button></form>
 </div>
 </div>
</div>