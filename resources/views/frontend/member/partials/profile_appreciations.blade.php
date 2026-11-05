@php
    $profileId = (int) $person->id;
    $liked = \App\Models\ProfileLike::where('sender_id', auth()->id())->where('recipient_id', $profileId)->exists();
@endphp
{{-- Reference card: View Profile + one Like icon + a three-dot menu.
     Super Like and Shortlist remain functional inside the flyout. --}}
<div class="hm-profile-appreciations hm-card-icons" data-member-name="{{ trim($person->first_name.' '.$person->last_name) }}">
    <button type="button" class="hm-appreciate-like {{ $liked ? 'is-active' : '' }}"
        data-like-url="{{ route('member.profile.like', $profileId) }}"
        aria-pressed="{{ $liked ? 'true' : 'false' }}"
        title="{{ $liked ? 'Unlike' : 'Like' }}"
        aria-label="{{ $liked ? 'Unlike' : 'Like' }} {{ $person->first_name }}">
        <i class="{{ $liked ? 'las' : 'lar' }} la-heart" aria-hidden="true"></i><span>{{ $liked ? 'Liked' : 'Like' }}</span>
    </button>
    @include('frontend.member.partials.match_dropdown', ['person' => $person, 'detailUrl' => $detailUrl, 'matchActions' => $matchActions])
</div>
