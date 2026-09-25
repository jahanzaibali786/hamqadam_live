@extends('frontend.layouts.member_panel')

@section('panel_content')
    <h1 class="fs-20 fw-700 mb-1">{{ translate('Guardian Permissions') }}</h1>
    <p class="text-muted fs-12">{{ translate('For') }}: <strong>{{ $link->guardian?->first_name }} {{ $link->guardian?->last_name }}</strong> — {{ $link->relationship }}</p>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('guardian_mode.permissions.update', $link->id) }}" id="permForm">
                @csrf
                <div class="row">
                    @foreach ($permissionCatalog as $key => $label)
                        @php
                            $checked = $current->has($key) ? (bool) $current[$key] : in_array($key, $presets['view_only']);
                        @endphp
                        <div class="col-md-6">
                            <div class="form-check py-1">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="perm_{{ $key }}" @checked($checked)>
                                <label class="form-check-label fs-13" for="perm_{{ $key }}">{{ translate($label) }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                @php
                    $neverDefault = ['send_interest', 'view_private_photos', 'view_private_chat', 'view_contact_details', 'manage_other_guardians', 'view_payments', 'account_security', 'delete_account'];
                @endphp
                <div class="alert alert-warning fs-12 mt-3">
                    {{ translate('Sensitive permissions (private chat, contacts, payments, account security) are strongly discouraged and are never enabled by default.') }}
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-primary">{{ translate('Save Permissions') }}</button>
                    <a href="{{ route('guardian_mode.index') }}" class="btn btn-outline-secondary">{{ translate('Back') }}</a>
                </div>
            </form>

            <div class="mt-3">
                <span class="fs-12 text-muted">{{ translate('Quick apply:') }}</span>
                @foreach (['view_only' => 'View Only', 'review' => 'Review', 'participate' => 'Participate'] as $presetKey => $presetLabel)
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1 preset-btn"
                            data-keys="{{ implode(',', $presets[$presetKey]) }}">{{ translate($presetLabel) }}</button>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.querySelectorAll('.preset-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var keys = btn.dataset.keys.split(',');
                document.querySelectorAll('#permForm input[type=checkbox]').forEach(function (cb) {
                    cb.checked = keys.includes(cb.value);
                });
            });
        });
    </script>
@endsection
