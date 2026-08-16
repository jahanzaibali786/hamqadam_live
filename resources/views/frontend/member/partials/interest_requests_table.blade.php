<table class="table aiz-table mb-0">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ translate('Image') }}</th>
            <th>{{ translate('Name') }}</th>
            <th>{{ translate('Age') }}</th>
            <th class="text-center">{{ translate('Status') }}</th>
            <th class="text-center">{{ translate('Action') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($interests as $key => $interest)
            @php
                $interested_by = $interest->sender ?: \App\Models\User::where('id', $interest->interested_by)->first();
                $statusValue = $interest->status instanceof \BackedEnum ? $interest->status->value : (int) $interest->status;
                $statusLabel = $interest->status instanceof \App\Enums\ProposalStatus
                    ? $interest->status->label()
                    : match ($statusValue) {
                        1 => 'accepted',
                        2 => 'rejected',
                        3 => 'withdrawn',
                        4 => 'cancelled',
                        5 => 'expired',
                        default => 'pending',
                    };
                $statusBadge = match ($statusValue) {
                    1 => 'badge-success',
                    2, 4, 5 => 'badge-danger',
                    3 => 'badge-secondary',
                    default => 'badge-warning',
                };
            @endphp
            @if ($interested_by != null)
                <tr id="interested_member_{{ $interested_by->id }}">
                    <td>{{ $key + 1 + ($interests->currentPage() - 1) * $interests->perPage() }}</td>
                    <td>
                        <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                            @else
                                href="{{ route('member_profile', $interested_by->id) }}" @endif
                            class="text-reset c-pointer">
                            @if (uploaded_asset($interested_by->photo) != null)
                                <img class="img-md" src="{{ uploaded_asset($interested_by->photo) }}"
                                    height="45px" alt="{{ translate('photo') }}">
                            @else
                                <img class="img-md" src="{{ static_asset('assets/img/avatar-place.png') }}"
                                    height="45px" alt="{{ translate('photo') }}">
                            @endif
                        </a>
                    </td>
                    <td>
                        <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                            @else
                                href="{{ route('member_profile', $interested_by->id) }}" @endif
                            class="text-reset c-pointer">
                            {{ $interested_by->first_name . ' ' . $interested_by->last_name }}
                        </a>
                    </td>
                    <td>
                        @if($interested_by->member?->birthday)
                            {{ \Carbon\Carbon::parse($interested_by->member->birthday)->age }}
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-inline {{ $statusBadge }}">{{ translate(ucfirst($statusLabel)) }}</span>
                    </td>
                    <td class="text-center">
                        @if ($statusValue === 0)
                            <a href="javascript:void(0);" onclick="accept_interest({{ $interest->id }})"
                                class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                title="{{ translate('Accept') }}">
                                <i class="las la-check"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="reject_interest({{ $interest->id }})"
                                class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                title="{{ translate('Reject') }}">
                                <i class="las la-trash"></i>
                            </a>
                        @elseif ($statusValue === 1)
                            <a href="javascript:void(0);" onclick="remove_interest({{ $interest->id }})"
                                class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                title="{{ translate('Remove') }}">
                                <i class="las la-trash"></i>
                            </a>
                        @else
                            <span class="text-muted fs-12">{{ translate('No action') }}</span>
                        @endif
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    {{ translate('No interest requests found.') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="aiz-pagination">
    {{ $interests->links() }}
</div>
