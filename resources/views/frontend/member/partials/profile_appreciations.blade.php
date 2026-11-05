@php
    $profileId = (int) $person->id;
    $liked = \App\Models\ProfileLike::where('sender_id', auth()->id())->where('recipient_id', $profileId)->exists();
    $superLiked = \App\Models\ProfileSuperLike::where('sender_id', auth()->id())->where('recipient_id', $profileId)->exists();
    $shortlisted = \App\Models\Shortlist::where('user_id', $profileId)->where('shortlisted_by', auth()->id())->exists();
@endphp
<div class="hm-profile-appreciations hm-card-icons" data-member-name="{{ trim($person->first_name.' '.$person->last_name) }}">
    <button type="button" class="hm-appreciate-like {{ $liked ? 'is-active' : '' }}"
        data-like-url="{{ route('member.profile.like', $profileId) }}"
        aria-pressed="{{ $liked ? 'true' : 'false' }}"
        title="{{ $liked ? 'Unlike' : 'Like' }}"
        aria-label="{{ $liked ? 'Unlike' : 'Like' }} {{ $person->first_name }}">
        <i class="{{ $liked ? 'las' : 'lar' }} la-heart" aria-hidden="true"></i><span>{{ $liked ? 'Liked' : 'Like' }}</span>
    </button>
    <button type="button" class="hm-appreciate-super {{ $superLiked ? 'is-active' : '' }}"
        data-super-url="{{ route('member.profile.superlike', $profileId) }}"
        @disabled($superLiked)
        aria-pressed="{{ $superLiked ? 'true' : 'false' }}"
        title="{{ $superLiked ? 'Super Liked' : 'Super Like' }}"
        aria-label="Super Like {{ $person->first_name }}">
        <i class="lar la-star" aria-hidden="true"></i><span>{{ $superLiked ? 'Super Liked' : 'Super Like' }}</span>
    </button>
    <button type="button" class="hm-appreciate-shortlist {{ $shortlisted ? 'is-active' : '' }}"
        data-shortlist-id="{{ $profileId }}"
        data-shortlist-add-url="{{ route('member.add_to_shortlist') }}"
        data-shortlist-remove-url="{{ route('member.remove_from_shortlist') }}"
        aria-pressed="{{ $shortlisted ? 'true' : 'false' }}"
        title="{{ $shortlisted ? 'Remove from Shortlist' : 'Shortlist' }}"
        aria-label="{{ $shortlisted ? 'Remove from Shortlist' : 'Shortlist' }} {{ $person->first_name }}">
        <i class="{{ $shortlisted ? 'las' : 'lar' }} la-bookmark" aria-hidden="true"></i><span>{{ $shortlisted ? 'Shortlisted' : 'Shortlist' }}</span>
    </button>
</div>
