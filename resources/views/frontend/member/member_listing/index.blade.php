@extends('frontend.layouts.app')
@section('content')
    <section class="hq-reference-page-hero hq-discovery-reference-hero">
        <div class="container">
            <!-- <div class="hq-reference-breadcrumb">{{ translate('Home') }} <span>/</span> {{ translate('Active Members Discovery') }}</div> -->
            <span class="hq-reference-eyebrow"><i class="las la-certificate"></i> {{ translate('Curated Sanctuary of Verified Hearts') }}</span>
            <h1>{{ translate('Active Members Discovery') }}</h1>
            <p>{{ translate('Explore respectful prospective matches with verified identities, family-minded discovery, and private communication.') }}</p>
            <div class="hq-discovery-meta">
                <span><i class="las la-circle"></i> {{ translate('Active Profiles') }}</span>
                <span><i class="las la-shield-alt"></i> {{ translate('Verified Community') }}</span>
                <span><i class="las la-comments"></i> {{ translate('Protected Communication') }}</span>
            </div>
        </div>
    </section>
    <section class="hq-reference-discovery-page py-4 py-lg-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-xl-3">
                            @include('frontend.member.member_listing.advanced_search')
                        </div>
                        <div class="col-xl-9">
                            <div class="d-flex">
                                <h1 class="h4 fw-600 mb-3 text-body">{{ translate('All Active Members') }}</h1>
                                <div class="d-xl-none ml-auto mb-1 ml-xl-3 mr-0 align-self-end">
                                    <button type="button" class="btn btn-icon p-0 round-btn" data-toggle="class-toggle"
                                        data-target=".aiz-filter-sidebar">
                                        <i class="la la-list la-2x"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-5 hq-discovery-grid">
                                @foreach ($users as $key => $user)
                                    @php
                                        $compatibility = $user->profile_match_for_viewer;
                                        $member = $user->member;
                                        $avatar_image = ($member?->gender == 1) ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                        $profile_picture_show = show_profile_picture($user);
                                        $isVerified = app(\App\Services\BadgeService::class)->isVerified($member);
                                        $isOnline = Cache::has('user-is-online-' . $user->id);
                                        $age = !empty($member?->birthday) ? \Carbon\Carbon::parse($member->birthday)->age : null;
                                        $height = $user->physical_attributes?->height;
                                        $presentAddress = $user->addresses->where('type', 'present')->first();
                                        $locationParts = array_values(array_filter([
                                            $presentAddress?->city?->name ?? null,
                                            $presentAddress?->state?->name ?? null,
                                            $presentAddress?->country?->name ?? null,
                                        ]));
                                        $locationText = implode(', ', array_slice($locationParts, 0, 2));
                                        $career = $user->career->last();
                                        $education = $user->education->where('is_highest_degree', 1)->first() ?? $user->education->last();
                                        $religionName = $user->spiritual_backgrounds?->religion?->name;
                                        $casteName = $user->spiritual_backgrounds?->caste?->name;
                                        $maritalStatus = $member?->marital_status?->name;
                                        $languageName = null;
                                        if (!empty($member?->mothere_tongue)) {
                                            $languageName = \App\Models\MemberLanguage::where('id', $member->mothere_tongue)->value('name');
                                        }

                                        $remaining_full_profile_views = (int) get_remaining_package_value(Auth::user()->id, 'remaining_profile_viewer_view');
                                        $can_view_full_profile = package_validity(Auth::user()->id) && $remaining_full_profile_views > 0;
                                        $already_viewed_full_profile = \App\Models\ProfileViewer::where('user_id', $user->id)->where('viewed_by', Auth::user()->id)->exists();
                                        $profileNeedsUpgrade = get_setting('full_profile_show_according_to_membership') == 1
                                            && !$already_viewed_full_profile
                                            && !$can_view_full_profile;

                                        $interest_class = 'text-primary';
                                        $do_expressed_interest = \App\Models\ExpressInterest::where('user_id', $user->id)
                                            ->where('interested_by', Auth::user()->id)
                                            ->first();
                                        $received_expressed_interest = \App\Models\ExpressInterest::where('user_id', Auth::user()->id)
                                            ->where('interested_by', $user->id)
                                            ->first();
                                        if (!empty($do_expressed_interest)) {
                                            $interest_onclick = 0;
                                            $interest_text = $do_expressed_interest->status->value == 0 ? translate('Interest Sent') : translate('Interest Accepted');
                                        } elseif (!empty($received_expressed_interest)) {
                                            $interest_onclick = 'do_response';
                                            $interest_text = $received_expressed_interest->status->value == 0 ? translate('Respond to Interest') : translate('Interest Accepted');
                                        } else {
                                            $interest_onclick = 1;
                                            $interest_text = translate('Send Interest');
                                        }

                                        $shortlist = \App\Models\Shortlist::where('user_id', $user->id)
                                            ->where('shortlisted_by', Auth::user()->id)
                                            ->first();
                                        $can_shortlist = (!empty($do_expressed_interest) && $do_expressed_interest->status->value == 1)
                                            || (!empty($received_expressed_interest) && $received_expressed_interest->status->value == 1);
                                        if (empty($shortlist) && $can_shortlist) {
                                            $shortlist_onclick = 1;
                                            $shortlist_text = translate('Shortlist');
                                            $shortlist_prompt = '';
                                        } elseif (!empty($shortlist)) {
                                            $shortlist_onclick = 0;
                                            $shortlist_text = translate('Shortlisted');
                                            $shortlist_prompt = '';
                                        } else {
                                            $shortlist_onclick = -1;
                                            $shortlist_text = translate('Shortlist');
                                            $shortlist_prompt = empty($do_expressed_interest) && empty($received_expressed_interest)
                                                ? translate('Please express interest first before shortlisting this member.')
                                                : translate('Please wait for interest approval before shortlisting this member.');
                                        }

                                        $profile_reported = \App\Models\ReportedUser::where('user_id', $user->id)
                                            ->where('reported_by', Auth::user()->id)
                                            ->exists();
                                    @endphp

                                    <article class="hq-discovery-card" id="block_id_{{ $user->id }}">
                                        <div class="hq-discovery-photo">
                                            <img
                                                src="{{ $profile_picture_show ? (uploaded_asset($user->photo) ?: static_asset($avatar_image)) : static_asset($avatar_image) }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
                                                alt="{{ $user->first_name . ' ' . $user->last_name }}">

                                            <div class="hq-profile-badges">
                                                @if($isVerified)
                                                    <span class="hq-verified-tag"><i class="las la-certificate"></i> {{ translate('CNIC Verified') }}</span>
                                                @endif
                                                <span class="hq-active-tag {{ $isOnline ? 'is-online' : '' }}">
                                                    <i class="las la-circle"></i> {{ $isOnline ? translate('Online Now') : translate('Active Recently') }}
                                                </span>
                                            </div>

                                            <a id="shortlist_a_id_{{ $user->id }}"
                                                @if ($shortlist_onclick == 1) onclick="do_shortlist({{ $user->id }})"
                                                @elseif($shortlist_onclick == 0) onclick="remove_shortlist({{ $user->id }})"
                                                @else onclick="show_shortlist_requirement(@json($shortlist_prompt))" @endif
                                                class="hq-card-heart c-pointer" title="{{ $shortlist_text }}">
                                                <i class="{{ $shortlist ? 'las' : 'lar' }} la-heart"></i>
                                            </a>

                                            <div class="hq-discovery-photo-overlay">
                                                <div>
                                                    <h2>{{ $user->first_name . ' ' . $user->last_name }}</h2>
                                                    <small>ID: {{ $user->code }}</small>
                                                </div>
                                                @if($compatibility)
                                                    <span class="hq-match-pill"><i class="las la-heart"></i> {{ (int) $compatibility->match_percentage }}% {{ translate('Match') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="hq-discovery-body">
                                            <div class="hq-profile-facts">
                                                @if($age)<span><i class="las la-user"></i>{{ $age }} {{ translate('Years') }}</span>@endif
                                                @if($height)<span><i class="las la-ruler-vertical"></i>{{ $height }}</span>@endif
                                                @if($locationText)<span><i class="las la-map-marker"></i>{{ $locationText }}</span>@endif
                                            </div>

                                            @if($career?->designation || $education?->degree)
                                                <div class="hq-profile-profession">
                                                    <i class="las la-graduation-cap"></i>
                                                    <span>{{ $career?->designation ?: $education?->degree }}</span>
                                                    @if($religionName)<b>• {{ $religionName }}</b>@endif
                                                </div>
                                            @endif

                                            <p class="hq-profile-summary">
                                                {{ \Illuminate\Support\Str::limit($compatibility?->compatibility_explanation ?: translate('A verified Hamqadam member seeking a respectful, family-minded lifelong partnership.'), 145) }}
                                            </p>

                                            <div class="hq-profile-tags">
                                                @foreach(array_filter([$languageName, $casteName, $maritalStatus]) as $tag)
                                                    <span>{{ $tag }}</span>
                                                @endforeach
                                                @if($member?->trust_badge)<span>{{ translate('Trust Badge') }}</span>@endif
                                            </div>

                                            <div class="hq-discovery-actions">
                                                @if($interest_onclick === 1)
                                                    <button type="button" id="interest_a_id_{{ $user->id }}" onclick="express_interest({{ $user->id }})" class="btn btn-primary">
                                                        <i class="lar la-heart"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </button>
                                                @elseif($interest_onclick === 'do_response')
                                                    <a id="interest_a_id_{{ $user->id }}" href="{{ route('interest_requests') }}" class="btn btn-primary">
                                                        <i class="las la-reply"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </a>
                                                @else
                                                    <button type="button" id="interest_a_id_{{ $user->id }}" class="btn btn-soft-primary" disabled>
                                                        <i class="las la-check"></i><span id="interest_id_{{ $user->id }}">{{ $interest_text }}</span>
                                                    </button>
                                                @endif

                                                @if($profileNeedsUpgrade)
                                                    <button type="button" onclick="package_update_alert()" class="btn btn-soft-primary"><i class="las la-user-lock"></i>{{ translate('View Profile') }}</button>
                                                @else
                                                    <a href="{{ route('member_profile', $user->id) }}" class="btn btn-soft-primary"><i class="las la-user"></i>{{ translate('View Profile') }}</a>
                                                @endif
                                            </div>

                                            <div class="hq-card-secondary-actions">
                                                <button type="button" onclick="ignore_member({{ $user->id }})"><i class="las la-ban"></i>{{ translate('Ignore') }}</button>
                                                <button type="button" @if(!$profile_reported) onclick="report_member({{ $user->id }})" @endif {{ $profile_reported ? 'disabled' : '' }}><i class="las la-flag"></i>{{ $profile_reported ? translate('Reported') : translate('Report') }}</button>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="aiz-pagination">
                                {{ $users->appends(request()->input())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('modal')
    @include('modals.package_update_alert_modal')
    @include('modals.confirm_modal')

    <!-- Ignore Modal -->
    <div class="modal fade ignore_member_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Ignore Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Are you sure that you want to ignore this member?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light round-btn" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="submit" class="btn btn-primary round-btn" id="ignore_button">{{ translate('Ignore') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Profile -->
    <div class="modal fade report_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Report Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('reportusers.store') }}" id="report-modal-form" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" id="member_id" value="">
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{ translate('Report Reason') }}<span
                                    class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <textarea name="reason" rows="4" class="form-control" placeholder="{{ translate('Report Reason') }}"
                                    required></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light round-btn" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary round-btn"
                        onclick="submitReport()">{{ translate('Report') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            get_castes_by_religion();
            get_sub_castes_by_caste();
            get_states_by_country();
            get_cities_by_state()
        });

        // Get Castes and Subcastes
        function get_castes_by_religion() {
            var religion_id = $('#religion_id').val();
            $.post('{{ route('castes.get_caste_by_religion') }}', {
                _token: '{{ csrf_token() }}',
                religion_id: religion_id
            }, function(data) {
                $('#caste_id').html(null);
                $('#caste_id').append($('<option>', {
                    value: '',
                    text: 'Choose One'
                }));
                for (var i = 0; i < data.length; i++) {
                    $('#caste_id').append($('<option>', {
                        value: data[i].id,
                        text: data[i].name
                    }));
                }
                $("#caste_id > option").each(function() {
                    if (this.value == '{{ $caste_id }}') {
                        $("#caste_id").val(this.value).change();
                    }
                });
                AIZ.plugins.bootstrapSelect('refresh');

                get_sub_castes_by_caste();
            });
        }

        function get_sub_castes_by_caste() {
            var caste_id = $('#caste_id').val();
            $.post('{{ route('sub_castes.get_sub_castes_by_religion') }}', {
                _token: '{{ csrf_token() }}',
                caste_id: caste_id
            }, function(data) {
                $('#sub_caste_id').html(null);
                $('#sub_caste_id').append($('<option>', {
                    value: '',
                    text: 'Choose One'
                }));
                for (var i = 0; i < data.length; i++) {
                    $('#sub_caste_id').append($('<option>', {
                        value: data[i].id,
                        text: data[i].name
                    }));
                }
                $("#sub_caste_id > option").each(function() {
                    if (this.value == '{{ $sub_caste_id }}') {
                        $("#sub_caste_id").val(this.value).change();
                    }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

        $('#religion_id').on('change', function() {
            get_castes_by_religion();
        });

        $('#caste_id').on('change', function() {
            get_sub_castes_by_caste();
        });

        // Get Countries and States
        function get_states_by_country() {
            var country_id = $('#country_id').val();
            $.post('{{ route('states.get_state_by_country') }}', {
                _token: '{{ csrf_token() }}',
                country_id: country_id
            }, function(data) {
                $('#state_id').html(null);
                $('#state_id').append($('<option>', {
                    value: '',
                    text: 'Choose One'
                }));
                for (var i = 0; i < data.length; i++) {
                    $('#state_id').append($('<option>', {
                        value: data[i].id,
                        text: data[i].name
                    }));
                }
                $("#state_id > option").each(function() {
                    if (this.value == '{{ $state_id }}') {
                        $("#state_id").val(this.value).change();
                    }
                });

                AIZ.plugins.bootstrapSelect('refresh');

                get_cities_by_state();
            });
        }

        function get_cities_by_state() {
            var state_id = $('#state_id').val();
            $.post('{{ route('cities.get_cities_by_state') }}', {
                _token: '{{ csrf_token() }}',
                state_id: state_id
            }, function(data) {
                $('#city_id').html(null);
                $('#city_id').append($('<option>', {
                    value: '',
                    text: 'Choose One'
                }));
                for (var i = 0; i < data.length; i++) {
                    $('#city_id').append($('<option>', {
                        value: data[i].id,
                        text: data[i].name
                    }));
                }
                $("#city_id > option").each(function() {
                    if (this.value == '{{ $city_id }}') {
                        $("#city_id").val(this.value).change();
                    }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

        $('#country_id').on('change', function() {
            get_states_by_country();
        });

        $('#state_id').on('change', function() {
            get_cities_by_state();
        });

        // Full Profile view
        function package_update_alert() {
            $('.package_update_alert_modal').modal('show');
        }

        // Express Interest
        var package_validity = false;
        var express_interest_coin_charge = {{ feature_coin_cost('express_interest', 1) }};
        var shortlist_coin_charge = {{ feature_coin_cost('shortlist', 5) }};
        @if(package_validity(Auth::user()->id))
            package_validity = true;
        @endif

        function express_interest(id) {
            var user_id = {{ Auth::user()->id }}
            $.post('{{ route('user.remaining_package_value') }}', {
                _token: '{{ csrf_token() }}',
                id: user_id,
                colmn_name: 'remaining_interest'
            }, function(data) {

                var remaining_interest = data;
                if (!package_validity || remaining_interest < 1) {
                    $('.package_update_alert_modal').modal('show');
                } else {
                    $('.confirm_modal').modal('show');
                    $("#confirm_modal_title").html("{{ translate('Confirm Express Interest!') }}");
                    $("#confirm_modal_content").html(
                        "<p class='fs-14'>{{ translate('Coin Balance') }}: " +
                        remaining_interest +
                        " {{ translate('Coins') }}</p><small class='text-danger fs-12' >{{ translate('**N.B. Expressing an interest will cost') }} " + express_interest_coin_charge + " {{ translate('coin(s)**') }}</small>"
                    );
                    $("#confirm_button").attr("onclick", "do_express_interest(" + id + ")");

                }
            });
        }

        function do_express_interest(id) {
            $('.confirm_modal').modal('hide');
            $("#interest_a_id_" + id).removeAttr("onclick");
            $("#interest_id_" + id).html("{{ translate('Processing') }}..");
            $.post('{{ route('express-interest.store') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                function(data) {
                    // console.log(data);
                    if (data) {
                        $("#interest_id_" + id).html("{{ translate('Interest Expressed') }}");
                        $("#interest_id_" + id).attr("class", "d-block fs-10 opacity-60 text-primary");
                        AIZ.plugins.notify('success', '{{ translate('Interest Expressed Sucessfully') }}');
                    } else {
                        $("#interest_id_" + id).html("{{ translate('Interest') }}");
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        function show_shortlist_requirement(message) {
            AIZ.plugins.notify('danger', message || '{{ translate('Please express interest first and wait for approval before shortlisting.') }}');
        }

        // Shortlist
        function do_shortlist(id) {
            $.post('{{ route('user.remaining_package_value') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    colmn_name: 'remaining_interest'
                },
                function(data) {
                    var remaining_interest = data;
                    if (!package_validity || remaining_interest < 5) {
                        $('.package_update_alert_modal').modal('show');
                    } else {
                        $('.confirm_modal').modal('show');
                        $("#confirm_modal_title").html("{{ translate('Confirm Shortlist!') }}");
                        $("#confirm_modal_content").html(
                            "<p class='fs-14'>{{ translate('Coin Balance') }}: " +
                            remaining_interest +
                            " {{ translate('Coins') }}</p><small class='text-danger fs-12'>{{ translate('**N.B. Shortlisting will cost') }} " + shortlist_coin_charge + " {{ translate('coin(s)**') }}</small>"
                        );
                        $("#confirm_button").attr("onclick", "do_shortlist_confirm(" + id + ")");
                    }
                }
            );
        }

        function do_shortlist_confirm(id) {
            $('.confirm_modal').modal('hide');
            $("#shortlist_a_id_" + id).removeAttr("onclick");
            $("#shortlist_id_" + id).html("{{ translate('Shortlisting') }}..");
            $.post('{{ route('member.add_to_shortlist') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                function(data) {
                    if (data == 1) {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlisted') }}");
                        $("#shortlist_id_" + id).attr("class", "d-block fs-10 opacity-60 text-primary");
                        $("#shortlist_a_id_" + id).attr("onclick", "remove_shortlist(" + id + ")");
                        AIZ.plugins.notify('success', '{{ translate('You Have Shortlisted This Member.') }}');
                    } else {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                        AIZ.plugins.notify('danger', '{{ translate('Please express interest first and wait for approval before shortlisting.') }}');
                    }
                }
            );
        }

        function remove_shortlist(id) {
            $("#shortlist_a_id_" + id).removeAttr("onclick");
            $("#shortlist_id_" + id).html("{{ translate('Removing') }}..");
            $.post('{{ route('member.remove_from_shortlist') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                function(data) {
                    if (data == 1) {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                        $("#shortlist_id_" + id).attr("class", "d-block fs-10 opacity-60 text-dark");
                        $("#shortlist_a_id_" + id).attr("onclick", "do_shortlist(" + id + ")");
                        AIZ.plugins.notify('success',
                            '{{ translate('You Have Removed This Member From Your Shortlist.') }}');
                    } else {
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        // Ignore
        function ignore_member(id) {
            $('.ignore_member_modal').modal('show');
            $("#ignore_button").attr("onclick", "do_ignore(" + id + ")");
        }

        function do_ignore(id) {
            // Prevent multiple request sending
            $("#ignore_button").removeAttr("onclick");
            $('.ignore_member_modal').modal('hide');

            $.post('{{ route('member.add_to_ignore_list') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                function(data) {
                    if (data == 1) {
                        $("#block_id_" + id).hide();
                        AIZ.plugins.notify('success', '{{ translate('You Have Ignored This Member.') }}');
                    } else {
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );

        }

        function report_member(id) {
            $('.report_modal').modal('show');
            $('#member_id').val(id);
        }

        function submitReport() {
            $('#report-modal-form').submit();
        }
    </script>
@endsection


