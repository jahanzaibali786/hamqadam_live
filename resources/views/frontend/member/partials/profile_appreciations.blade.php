@php
    $profileId=(int)$person->id;
    $liked=\App\Models\ProfileLike::where('sender_id',auth()->id())->where('recipient_id',$profileId)->exists();
    $superLiked=\App\Models\ProfileSuperLike::where('sender_id',auth()->id())->where('recipient_id',$profileId)->exists();
@endphp
<div class="hm-profile-appreciations" data-member-name="{{ trim($person->first_name.' '.$person->last_name) }}">
  <button type="button" class="hm-appreciate-like {{ $liked?'is-active':'' }}" data-like-url="{{ route('member.profile.like',$profileId) }}" aria-pressed="{{ $liked?'true':'false' }}" title="Free Like" aria-label="{{ $liked?'Unlike':'Like' }} {{ $person->first_name }}"><i class="{{ $liked?'las':'lar' }} la-heart"></i><span>{{ $liked?'Liked':'Like' }}</span></button>
  <button type="button" class="hm-appreciate-super {{ $superLiked?'is-active':'' }}" data-super-url="{{ route('member.profile.superlike',$profileId) }}" @disabled($superLiked) title="Premium Super Like" aria-label="Super Like {{ $person->first_name }}"><i class="las la-star"></i><span>{{ $superLiked?'Super Liked':'Super Like' }}</span></button>
</div>