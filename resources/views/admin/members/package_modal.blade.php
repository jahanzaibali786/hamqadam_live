<div class="modal-header">
    <h5 class="modal-title h6">{{translate('Running Package Information')}}</h5>
    <button type="button" class="close" data-dismiss="modal">
    </button>
</div>
@if(!$member)
<div class="modal-body">
    <p class="text-danger mb-0">{{ translate('This member has no profile record yet, so a package cannot be assigned. Please open the member once from the app so their profile is created.') }}</p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
</div>
@else
<div class="modal-body">
    <table class="table table-bordered table-sm mb-0">
        <tbody>
            <tr>
                <th>{{translate('Package Name')}}</th>
                {{-- Free/Basic members have no current_package_id yet — the old
                     `$member->package->name` threw "Attempt to read property
                     'name' on null", which 500'd the AJAX modal and made the
                     Upgrade button do nothing for exactly those members. --}}
                <td>{{ $member && $member->package ? $member->package->name : translate('No active package') }}</td>
            </tr>
            <tr>
                <th>{{translate('Coin Balance')}}</th>
                <td>{{ $member->remaining_interest }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Photo Gallery')}}</th>
                <td>{{ $member->remaining_photo_gallery }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Contact View')}}</th>
                <td>{{ $member->remaining_contact_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Profile Viewer View')}}</th>
                <td>{{ $member->remaining_profile_viewer_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Profile Image View')}}</th>
                <td>{{ $member->remaining_profile_image_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Gallery Image View')}}</th>
                <td>{{ $member->remaining_gallery_image_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Auto Profile Match Show')}}</th>
                <td>
                  @if($member->auto_profile_match == 1)
                      <span class="badge badge-inline badge-success">{{translate('On')}}</span>
                  @else
                      <span class="badge badge-inline badge-danger">{{translate('Off')}}</span>
                  @endif
                </td>
            </tr>
            <tr>
                <th>{{translate('Auto Horoscope Profile Match Show')}}</th>
                <td>
                  @if($member->auto_horoscope_profile_match == 1)
                      <span class="badge badge-inline badge-success">{{translate('On')}}</span>
                  @else
                      <span class="badge badge-inline badge-danger">{{translate('Off')}}</span>
                  @endif
                </td>
            </tr>
            <tr>
                <th>{{translate('Validity')}}</th>
                <td>
                  @if(package_validity($member->user_id))
                    {{ $member->package_validity }}
                  @else
                      <span class="badge badge-inline badge-danger">{{translate('Expired')}}</span>
                  @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button class="btn btn-success" onclick="get_package({{ $member->id }});">{{translate('Upgrade Package')}}</button>
</div>
@endif
