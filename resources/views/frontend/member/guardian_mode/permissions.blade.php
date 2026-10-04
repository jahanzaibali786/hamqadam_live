@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h1 class="fs-20 fw-700 mb-1">{{ translate('Guardian Permissions') }}</h1>
            <p class="text-muted fs-13 mb-0">
                {{ translate('Managing access for') }}: 
                <strong class="text-dark">{{ $link->guardian?->first_name }} {{ $link->guardian?->last_name }}</strong> 
                <span class="badge badge-inline badge-soft-primary ms-1">{{ $link->relationship }}</span>
                @if ($link->is_wali)
                    <span class="badge badge-inline badge-soft-success ms-1">{{ translate('Wali') }}</span>
                @endif
            </p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="{{ route('guardian_mode.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="las la-arrow-left"></i> {{ translate('Back to Guardians') }}
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            {{-- Master Select All Toggle --}}
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllPerms" style="cursor: pointer; width: 2.5rem; height: 1.3rem;">
                <label class="form-check-label fw-700 fs-14 text-dark ms-2" for="selectAllPerms" style="cursor: pointer; line-height: 1.4;">
                    {{ translate('Select All Permissions') }}
                </label>
            </div>

            {{-- Quick Presets --}}
            <div class="d-flex flex-wrap align-items-center gap-1">
                <span class="fs-12 text-muted me-1">{{ translate('Presets:') }}</span>
                <button type="button" class="btn btn-xs btn-outline-secondary preset-btn" data-keys="{{ implode(',', $presets['view_only']) }}">
                    {{ translate('View Only') }}
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary preset-btn" data-keys="{{ implode(',', $presets['review']) }}">
                    {{ translate('Review') }}
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary preset-btn" data-keys="{{ implode(',', $presets['participate']) }}">
                    {{ translate('Participate') }}
                </button>
                <button type="button" class="btn btn-xs btn-outline-primary" id="btnCheckAll">
                    <i class="las la-check-double"></i> {{ translate('Select All') }}
                </button>
                <button type="button" class="btn btn-xs btn-outline-danger" id="btnUncheckAll">
                    <i class="las la-times"></i> {{ translate('Deselect All') }}
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('guardian_mode.permissions.update', $link->id) }}" id="permForm">
                @csrf

                @php
                    $categories = [
                        'discovery' => [
                            'title' => 'Profile Discovery & Data Access',
                            'badge' => 'Discovery',
                            'badge_class' => 'badge-soft-info',
                            'icon' => 'las la-eye',
                            'keys' => [
                                'view_basic_profile' => 'View approved basic profile',
                                'view_photo' => 'View approved profile photos',
                                'view_family_info' => 'View approved family details',
                                'view_verification_status' => 'View verification badges/status',
                                'view_ai_compatibility' => 'View compatibility summary',
                                'view_recommended_matches' => 'Review recommended matches',
                            ]
                        ],
                        'matchmaking' => [
                            'title' => 'Match Evaluation & Actions',
                            'badge' => 'Matchmaking',
                            'badge_class' => 'badge-soft-primary',
                            'icon' => 'las la-user-check',
                            'keys' => [
                                'shortlist_match' => 'Shortlist a profile',
                                'add_guardian_note' => 'Add guardian notes',
                                'recommend_match' => 'Recommend a match to the member',
                                'review_proposal' => 'Review proposals',
                                'approve_family_intro' => 'Approve family-stage actions',
                            ]
                        ],
                        'engagement' => [
                            'title' => 'Communication & Direct Engagement',
                            'badge' => 'Sensitive',
                            'badge_class' => 'badge-soft-warning',
                            'icon' => 'las la-comments',
                            'keys' => [
                                'send_interest' => 'Send interest on behalf of the member',
                                'view_private_photos' => 'View restricted photos',
                                'view_private_chat' => 'View personal chat',
                                'view_contact_details' => 'View phone/email contact details',
                            ]
                        ],
                        'account' => [
                            'title' => 'Account & Administrative Settings',
                            'badge' => 'Restricted',
                            'badge_class' => 'badge-soft-danger',
                            'icon' => 'las la-shield-alt',
                            'keys' => [
                                'manage_other_guardians' => 'Add/remove other guardians',
                                'view_payments' => 'View subscriptions and payments',
                                'account_security' => 'Change password/OTP/security',
                                'delete_account' => 'Delete the member account',
                            ]
                        ],
                    ];
                @endphp

                <div class="row g-4">
                    @foreach ($categories as $catKey => $category)
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h3 class="fs-14 fw-700 mb-0 d-flex align-items-center">
                                        <i class="{{ $category['icon'] }} fs-18 me-2 text-primary"></i>
                                        {{ translate($category['title']) }}
                                    </h3>
                                    <span class="badge {{ $category['badge_class'] }} fs-11">{{ translate($category['badge']) }}</span>
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    @foreach ($category['keys'] as $key => $defaultLabel)
                                        @php
                                            $label = $permissionCatalog[$key] ?? $defaultLabel;
                                            $checked = $current->has($key) ? (bool) $current[$key] : in_array($key, $presets['view_only']);
                                            $isSensitive = in_array($key, ['send_interest', 'view_private_photos', 'view_private_chat', 'view_contact_details', 'manage_other_guardians', 'view_payments', 'account_security', 'delete_account']);
                                        @endphp
                                        <div class="form-check d-flex align-items-center py-1 rounded px-2 hover-bg-light">
                                            <input class="form-check-input perm-checkbox me-2" type="checkbox" 
                                                   name="permissions[]" value="{{ $key }}" 
                                                   id="perm_{{ $key }}" @checked($checked)>
                                            <label class="form-check-label fs-13 w-100 {{ $isSensitive ? 'text-dark' : '' }}" for="perm_{{ $key }}" style="cursor: pointer;">
                                                {{ translate($label) }}
                                                @if ($isSensitive)
                                                    <span class="badge badge-inline badge-soft-warning fs-10 ms-1">{{ translate('Sensitive') }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-soft-warning fs-12 mt-4 d-flex align-items-center">
                    <i class="las la-exclamation-triangle fs-20 me-2"></i>
                    <div>
                        {{ translate('Sensitive permissions (private chat, contacts, payments, account security) grant deep access. By default, only safe review permissions are recommended.') }}
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-4 fw-600">
                        <i class="las la-save me-1"></i> {{ translate('Save Permissions') }}
                    </button>
                    <a href="{{ route('guardian_mode.index') }}" class="btn btn-outline-secondary px-4">
                        {{ translate('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selectAllCheckbox = document.getElementById('selectAllPerms');
            var checkboxes = document.querySelectorAll('.perm-checkbox');
            var btnCheckAll = document.getElementById('btnCheckAll');
            var btnUncheckAll = document.getElementById('btnUncheckAll');
            var presetButtons = document.querySelectorAll('.preset-btn');

            function syncSelectAllState() {
                var total = checkboxes.length;
                var checkedCount = 0;
                checkboxes.forEach(function (cb) {
                    if (cb.checked) checkedCount++;
                });

                if (checkedCount === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (checkedCount === total) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }

            // Master Select All checkbox toggle
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    var shouldCheck = this.checked;
                    checkboxes.forEach(function (cb) {
                        cb.checked = shouldCheck;
                    });
                    syncSelectAllState();
                });
            }

            // Quick Presets
            presetButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var keys = (btn.dataset.keys || '').split(',');
                    checkboxes.forEach(function (cb) {
                        cb.checked = keys.includes(cb.value);
                    });
                    syncSelectAllState();
                });
            });

            // Select All button
            if (btnCheckAll) {
                btnCheckAll.addEventListener('click', function () {
                    checkboxes.forEach(function (cb) {
                        cb.checked = true;
                    });
                    syncSelectAllState();
                });
            }

            // Deselect All button
            if (btnUncheckAll) {
                btnUncheckAll.addEventListener('click', function () {
                    checkboxes.forEach(function (cb) {
                        cb.checked = false;
                    });
                    syncSelectAllState();
                });
            }

            // Individual checkbox toggle listener
            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', syncSelectAllState);
            });

            // Initial sync on load
            syncSelectAllState();
        });
    </script>
@endsection
