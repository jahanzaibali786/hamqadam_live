<div class="hq-member-card rounded border position-relative overflow-hidden">
    <a
        @if(!Auth::check())
            onclick="loginModal()"
        @elseif(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)
            href="javascript:void(0);" onclick="package_update_alert()"
        @else
            href="{{ route('member_profile', $member->id) }}"
        @endif
        class="d-block text-reset c-pointer"
    >
        @php
            $avatar_image = (optional($member->member)->gender == 1) ? 'assets/img/avatar-place.png' : ((optional($member->member)->gender == 2) ? 'assets/img/female-avatar-place.png' : null);
            $profile_picture_show = show_profile_picture($member);
        @endphp
        <div class="hq-member-media">
            <img
                @if($profile_picture_show)
                    src="{{ uploaded_asset($member->photo) }}"
                @else
                    src="{{ static_asset($avatar_image) }}"
                @endif
                onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
                class="img-fit mw-100 h-350px {{ !$profile_picture_show ? 'hq-member-photo-locked' : '' }}"
            >
            @if(!$profile_picture_show)
                <div class="hq-member-lock d-flex justify-content-center align-items-center text-white">
                    <i class="las la-lock"></i>
                </div>
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