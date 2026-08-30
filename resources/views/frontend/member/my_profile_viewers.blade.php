@extends('frontend.layouts.member_panel')
@section('panel_content')
@php
    $user = auth()->user();
    $remainingFullProfileViews = (int) get_remaining_package_value($user->id, 'remaining_profile_viewer_view');
    $canViewFullProfile = package_validity($user->id) && $remainingFullProfileViews > 0;
    $activeTab = $activeTab ?? request('tab', 'received');
@endphp

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h5 class="mb-0 h6">{{ translate('Profile Views') }}</h5>
        <div class="btn-group" role="tablist" aria-label="Profile view tabs">
            <a href="{{ route('profile-viewers.index', ['tab' => 'received']) }}" class="btn btn-sm {{ $activeTab === 'received' ? 'btn-primary' : 'btn-light' }}">
                {{ translate('Viewed Me') }} <span class="badge badge-light ml-1">{{ $receivedProfileViewers->total() }}</span>
            </a>
            <a href="{{ route('profile-viewers.index', ['tab' => 'viewed']) }}" class="btn btn-sm {{ $activeTab === 'viewed' ? 'btn-primary' : 'btn-light' }}">
                {{ translate('I Viewed') }} <span class="badge badge-light ml-1">{{ $viewedProfiles->total() }}</span>
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'received' ? 'show active' : '' }}" id="tab-viewed-me">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Image') }}</th>
                            <th>{{ translate('Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Age') }}</th>
                            @if(get_setting('member_spiritual_and_social_background_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Religion') }}</th>
                            @endif
                            @if(get_setting('member_present_address_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Location') }}</th>
                            @endif
                            @if(get_setting('member_language_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Mother Tongue') }}</th>
                            @endif
                            <th class="text-right" data-breakpoints="lg">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receivedProfileViewers as $key => $profileViewer)
                            @php
                                $profileViewedBy = $profileViewer->profileViewer;
                                $alreadyViewedFullProfile = \App\Models\ProfileViewer::where('user_id', $profileViewedBy->id)->where('viewed_by', $user->id)->exists();
                                $openProfile = $alreadyViewedFullProfile || $canViewFullProfile;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($receivedProfileViewers->currentPage() - 1) * $receivedProfileViewers->perPage() }}</td>
                                <td>
                                    <a href="{{ $openProfile ? route('member_profile', $profileViewedBy->id) : 'javascript:void(0);' }}"
                                       @unless($openProfile) onclick="package_update_alert()" @endunless
                                       class="text-reset c-pointer">
                                        @if (uploaded_asset($profileViewedBy->photo) != null)
                                            <img class="img-md" src="{{ uploaded_asset($profileViewedBy->photo) }}" height="45px" alt="{{ translate('photo') }}">
                                        @else
                                            <img class="img-md" src="{{ static_asset('assets/img/avatar-place.png') }}" height="45px" alt="{{ translate('photo') }}">
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <a class="text-reset c-pointer"
                                       href="{{ $openProfile ? route('member_profile', $profileViewedBy->id) : 'javascript:void(0);' }}"
                                       @unless($openProfile) onclick="package_update_alert()" @endunless>
                                        {{ $profileViewedBy->first_name . ' ' . $profileViewedBy->last_name }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($profileViewedBy->member->birthday)->age }}</td>
                                @if (get_setting('member_spiritual_and_social_background_section') == 'on')
                                    <td>
                                        @if (!empty($profileViewedBy->spiritual_backgrounds->religion_id))
                                            {{ $profileViewedBy->spiritual_backgrounds->religion->name }}
                                        @endif
                                    </td>
                                @endif
                                @if (get_setting('member_present_address_section') == 'on')
                                    <td>
                                        @php $present_address = \App\Models\Address::where('type', 'present')->where('user_id', $profileViewedBy->id)->first(); @endphp
                                        @if (!empty($present_address->country_id))
                                            {{ $present_address->country->name }}
                                        @endif
                                    </td>
                                @endif
                                @if (get_setting('member_language_section') == 'on')
                                    <td>
                                        @if ($profileViewedBy->member->mothere_tongue != null)
                                            {{ \App\Models\MemberLanguage::where('id', $profileViewedBy->member->mothere_tongue)->first()->name }}
                                        @endif
                                    </td>
                                @endif
                                <td class="text-right">
                                    @if ($alreadyViewedFullProfile)
                                        <a href="{{ route('member_profile', $profileViewedBy->id) }}" class="text-success d-inline-block">
                                            <i class="las la-check-circle fs-20"></i>
                                            <span class="d-block fs-10 opacity-60">{{ translate('Viewed') }}</span>
                                        </a>
                                    @elseif ($canViewFullProfile)
                                        <a href="{{ route('member_profile', $profileViewedBy->id) }}" class="text-primary d-inline-block">
                                            <i class="las la-user fs-20"></i>
                                            <span class="d-block fs-10 opacity-60">{{ translate('Full Profile') }}</span>
                                        </a>
                                    @else
                                        <a href="javascript:void(0);" onclick="package_update_alert()" class="text-reset c-pointer d-inline-block">
                                            <i class="las la-user-lock fs-20 text-muted"></i>
                                            <span class="d-block fs-10 opacity-60">{{ translate('Upgrade to View') }}</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $receivedProfileViewers->appends(['tab' => 'received'])->links() }}
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'viewed' ? 'show active' : '' }}" id="tab-i-viewed">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Image') }}</th>
                            <th>{{ translate('Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Age') }}</th>
                            @if(get_setting('member_spiritual_and_social_background_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Religion') }}</th>
                            @endif
                            @if(get_setting('member_present_address_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Location') }}</th>
                            @endif
                            @if(get_setting('member_language_section') == 'on')
                                <th data-breakpoints="lg">{{ translate('Mother Tongue') }}</th>
                            @endif
                            <th class="text-right" data-breakpoints="lg">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewedProfiles as $key => $profileViewer)
                            @php
                                $profileViewed = $profileViewer->user;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($viewedProfiles->currentPage() - 1) * $viewedProfiles->perPage() }}</td>
                                <td>
                                    <a href="{{ route('member_profile', $profileViewed->id) }}" class="text-reset c-pointer">
                                        @if (uploaded_asset($profileViewed->photo) != null)
                                            <img class="img-md" src="{{ uploaded_asset($profileViewed->photo) }}" height="45px" alt="{{ translate('photo') }}">
                                        @else
                                            <img class="img-md" src="{{ static_asset('assets/img/avatar-place.png') }}" height="45px" alt="{{ translate('photo') }}">
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <a class="text-reset c-pointer" href="{{ route('member_profile', $profileViewed->id) }}">
                                        {{ $profileViewed->first_name . ' ' . $profileViewed->last_name }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($profileViewed->member->birthday)->age }}</td>
                                @if (get_setting('member_spiritual_and_social_background_section') == 'on')
                                    <td>
                                        @if (!empty($profileViewed->spiritual_backgrounds->religion_id))
                                            {{ $profileViewed->spiritual_backgrounds->religion->name }}
                                        @endif
                                    </td>
                                @endif
                                @if (get_setting('member_present_address_section') == 'on')
                                    <td>
                                        @php $present_address = \App\Models\Address::where('type', 'present')->where('user_id', $profileViewed->id)->first(); @endphp
                                        @if (!empty($present_address->country_id))
                                            {{ $present_address->country->name }}
                                        @endif
                                    </td>
                                @endif
                                @if (get_setting('member_language_section') == 'on')
                                    <td>
                                        @if ($profileViewed->member->mothere_tongue != null)
                                            {{ \App\Models\MemberLanguage::where('id', $profileViewed->member->mothere_tongue)->first()->name }}
                                        @endif
                                    </td>
                                @endif
                                <td class="text-right">
                                    <a href="{{ route('member_profile', $profileViewed->id) }}" class="text-success d-inline-block">
                                        <i class="las la-check-circle fs-20"></i>
                                        <span class="d-block fs-10 opacity-60">{{ translate('Viewed') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $viewedProfiles->appends(['tab' => 'viewed'])->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modal')
  @include('modals.confirm_modal')
  @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function package_update_alert(){
      $('.package_update_alert_modal').modal('show');
    }
</script>
@endsection
