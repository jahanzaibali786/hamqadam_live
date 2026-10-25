{{-- Shared by the Premium, Free, Basic and status-based member listing action menus. --}}
@can('edit_member')
    <div class="dropdown-divider"></div>
    <form method="POST"
          action="{{ route('members.website_visibility', $member->id) }}"
          class="px-3 py-2 mb-0"
          onclick="event.stopPropagation()">
        @csrf
        @method('PATCH')
        <input type="hidden" name="display_on_website" value="{{ $member->display_on_website ? '0' : '1' }}">
        <label class="d-flex align-items-center m-0" style="cursor:pointer;gap:9px">
            <input type="checkbox"
                   {{ $member->display_on_website ? 'checked' : '' }}
                   onchange="this.form.submit()"
                   aria-label="{{ translate('Featured on Website') }} — {{ $member->first_name }} {{ $member->last_name }}">
            <span class="small">{{ translate('Featured on Website') }}</span>
        </label>
    </form>
@endcan
