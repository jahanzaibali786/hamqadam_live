@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="card">
        @php
          $canRefresh = true;
          if(Auth::user()->match_refresh_updated_at){
            $nextRefresh = \Carbon\Carbon::parse(Auth::user()->match_refresh_updated_at)->addMinutes(30);
            if($nextRefresh->isFuture()){
                $canRefresh = false;
            }
          }
        @endphp
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0 h6">{{ translate('Matched Profiles') }}</h5>
          @if($canRefresh)
              <a href="{{ route('match.refresh') }}"
                class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                title="{{ translate('Refresh') }}">
                  <i class="las la-sync"></i>
              </a>
          @else
              <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm"
                      disabled
                      title="{{ translate('You can refresh after 30 minutes') }}">
                  <i class="las la-sync"></i>
              </button>
          @endif
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
              <thead>
                  <tr>
                      <th>#</th>
                      <th>{{translate('Image')}}</th>
                      <th>{{translate('Name')}}</th>
                      <th data-breakpoints="lg">{{translate('Compatibility')}}</th>
                      <th data-breakpoints="lg">{{translate('Age')}}</th>
                      @if(get_setting('member_spiritual_and_social_background_section') == 'on')
                        <th data-breakpoints="lg">{{translate('Religion')}}</th>
                      @endif
                      @if(get_setting('member_present_address_section') == 'on')
                        <th data-breakpoints="lg">{{translate('Location')}}</th>
                      @endif
                      @if(get_setting('member_language_section') == 'on')
                        <th data-breakpoints="lg">{{translate('Mother  Tongue')}}</th>
                      @endif
                      <th class="text-right" data-breakpoints="lg">{{translate('Options')}}</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach ($matchedProfiles as $key => $matchedProfile)
                      @php $matchedUser = $matchedProfile->matchedUser ?: $matchedProfile->user; @endphp
                      @if($matchedUser != null)
                      <tr>
                          <td>{{ ($key+1) + ($matchedProfiles->currentPage() - 1)*$matchedProfiles->perPage() }}</td>
                          <td>
                            <a
                              @if(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)
                                  href="javascript:void(0);" onclick="package_update_alert()"
                              @else
                                  href="{{ route('member_profile', $matchedUser->id) }}"
                              @endif
                              class="text-reset c-pointer"
                            >
                                @if(uploaded_asset($matchedUser->photo) != null)
                                    <img class="img-fit w-45px h-45px" src="{{ uploaded_asset($matchedUser->photo) }}" alt="{{translate('photo')}}">
                                @else
                                    <img class="img-fit w-45px h-45px" src="{{ static_asset('assets/img/avatar-place.png') }}" alt="{{translate('photo')}}">
                                @endif
                            </a>
                          </td>
                          <td>
                              <a
                                @if(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)
                                    href="javascript:void(0);" onclick="package_update_alert()"
                                @else
                                    href="{{ route('member_profile', $matchedUser->id) }}"
                                @endif
                                class="text-reset c-pointer"
                              >
                                {{ $matchedUser->first_name.' '.$matchedUser->last_name }}
                            </a>
                          </td>
                          <td>
                            <div class="d-flex align-items-center">
                              <span class="badge badge-inline badge-primary mr-2">{{ (int) $matchedProfile->match_percentage }}%</span>
                              <div class="progress flex-grow-1" style="height: 6px; min-width: 90px;">
                                <div class="progress-bar bg-primary" style="width: {{ (int) $matchedProfile->match_percentage }}%"></div>
                              </div>
                            </div>
                            <div class="fs-11 opacity-70 mt-1">
                              {{ $matchedProfile->compatibility_explanation ?: translate('Preference-based compatibility match') }}
                            </div>
                          </td>
                          <td>{{ \Carbon\Carbon::parse($matchedUser->member->birthday)->age }}</td>
                          @if(get_setting('member_spiritual_and_social_background_section') == 'on')
                          <td>
                            @if(!empty($matchedUser->spiritual_backgrounds->religion_id))
                                {{ $matchedUser->spiritual_backgrounds->religion->name }}
                            @endif
                          </td>
                          @endif
                          @if(get_setting('member_present_address_section') == 'on')
                          <td>
                            @php
                                $present_address = $matchedUser->addresses?->where('type','present')->first();
                            @endphp
                            @if(!empty($present_address->country_id))
                                {{ $present_address->country->name }}
                            @endif
                          </td>
                          @endif
                          @if(get_setting('member_language_section') == 'on')
                          <td>
                            @if($matchedUser->member->mothere_tongue != null)
                                {{ \App\Models\MemberLanguage::where('id',$matchedUser->member->mothere_tongue)->first()->name }}
                            @endif
                          </td>
                          @endif
                          <td class="text-right">
                              @php
                                $expressed_interest = \App\Models\ExpressInterest::where('user_id', $matchedUser->id)->where('interested_by',Auth::user()->id)->first();
                                $received_expressed_interest = \App\Models\ExpressInterest::where('user_id', Auth::user()->id)
                                                                ->where('interested_by', $matchedUser->id)
                                                                ->first();
                              @endphp
                              @if(empty($expressed_interest) && empty($received_expressed_interest))
                                <a href="javascript:void(0);" onclick="express_interest({{ $matchedUser->id }})" id="interest_a_id_{{ $matchedUser->id }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('Express Interest') }}">
                                    <i class="las la-heart"></i>
                                </a>
                              @elseif($received_expressed_interest)
                                @if($received_expressed_interest->status == 0)
                                  <a href="{{ route('interest_requests') }}" class="btn btn-soft-success btn-icon btn-circle btn-sm" title="{{ translate('Response to Interest') }}">
                                    <i class="las la-heart"></i>
                                  </a>
                                @else
                                  <a href="javascript:void(0);" class="btn btn-soft-success btn-icon btn-circle btn-sm" title="{{ translate('You Accepted Interest of This Member') }}">
                                    <i class="las la-heart"></i>
                                  </a>
                                @endif
                              @else
                                <a href="javascript:void(0);" class="btn btn-soft-success btn-icon btn-circle btn-sm" title="{{ translate('Interest Expressed') }}">
                                    <i class="las la-heart"></i>
                                </a>
                              @endif
                          </td>
                      </tr>
                    @endif

                  @endforeach
              </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $matchedProfiles->links() }}
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

  //package Validity
  var package_validity = false;
  @if(package_validity(Auth::user()->id))
      package_validity = true;
  @endif

  function express_interest(id)
  {
    var user_id = {{ Auth::user()->id }}
    $.post('{{ route('user.remaining_package_value') }}', {_token:'{{ csrf_token() }}', id:user_id, colmn_name:'remaining_interest' }, function(data){
        var remaining_interest = data;
        if(!package_validity || remaining_interest < 1){
            $('.package_update_alert_modal').modal('show');
        }
        else{
          $('.confirm_modal').modal('show');
          $("#confirm_modal_title").html("{{ translate('Confirm Express Interest') }}");
          $("#confirm_modal_content").html("<p class='fs-14'>{{translate('Coin Balance')}}: "+remaining_interest+" {{translate('Coins')}}</p><small class='text-danger fs-12'>{{translate('**N.B. Expressing an interest will cost 1 coin**')}}</small>");
          $("#confirm_button").attr("onclick","do_express_interest("+id+")");
        }
    });
  }

  function do_express_interest(id){
    $('.confirm_modal').modal('hide');
    $("#interest_a_id_"+id).removeAttr("onclick");
    $.post('{{ route('express-interest.store') }}',
      {
        _token: '{{ csrf_token() }}',
        id: id
      },
      function (data) {
        if (data) {
          $("#interest_a_id_"+id).attr("class","btn btn-soft-success btn-icon btn-circle btn-sm");
          $("#interest_a_id_"+id).attr("title","{{ translate('Interest Expressed') }}");
          AIZ.plugins.notify('success', '{{translate('Interest Expressed Sucessfully')}}');
        }
        else {
            AIZ.plugins.notify('danger', '{{translate('Something went wrong')}}');
        }
      }
    );
  }

  function package_update_alert(){
    $('.package_update_alert_modal').modal('show');
  }

</script>
@endsection
