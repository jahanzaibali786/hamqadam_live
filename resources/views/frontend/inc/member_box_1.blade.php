<div class="hq-member-card rounded border position-relative overflow-hidden">
    <a
        @if(!Auth::check())
            href="{{ route('login') }}"
        @elseif(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)
            href="javascript:void(0);" onclick="package_update_alert()"
        @else
            href="{{ route('member_profile', $member->id) }}"
        @endif
        class="d-block text-reset c-pointer"
    >
        @php
            // Guest visibility comes from explicit website featuring, not Auth::check().
            // Photo moderation and profile privacy are still enforced.
            $showPublicPhoto = (bool) $member->display_on_website
                && (bool) $member->approved
                && (bool) $member->photo_approved
                && !empty($member->photo)
                && !(bool) $member->member?->hide_profile
                && !(bool) $member->profile_privacy_setting?->invisible_mode;
            $memberPhoto = $showPublicPhoto ? uploaded_asset($member->photo) : null;
        @endphp
        <div class="hq-member-media hq-home-public-photo">
            <div class="hq-member-photo-placeholder" aria-hidden="true">
                <i class="las la-user-circle"></i>
            </div>
            @if ($showPublicPhoto)
                <img src="{{ $memberPhoto }}"
                     class="hq-member-photo-visible"
                     loading="lazy"
                     decoding="async"
                     alt="{{ $member->first_name }} — public profile photo"
                     onerror="this.hidden=true;">
            @endif
        </div>

        <div class="hq-member-info w-100 p-3 z-1">
            <div class="text-center">
                <div class="text-primary fw-500 mb-1">{{ $member->first_name }}</div>
                <div class="fs-10 mb-2">
                    <span class="opacity-60">{{ translate('Member ID: ') }}</span>
                    <span class="ml-1 text-primary">{{ $member->code }}</span>
                </div>
                <div class="hq-member-badges">
                    @if(app(\App\Services\BadgeService::class)->isVerified($member->member))
                        <span class="badge badge-soft-success mr-1"><i class="las la-shield-alt"></i> {{ translate('Verified') }} <i class="las la-check-circle"></i></span>
                    @endif
                    @if($member->member?->trust_badge)
                        <span class="badge badge-soft-warning"><i class="las la-shield-alt"></i> {{ translate('Trust') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </a>
</div>