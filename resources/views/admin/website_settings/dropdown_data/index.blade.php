@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Dropdowns Data') }}</h1>
            <p class="mb-0 text-muted">{{ translate('Manage registration dropdown data from one place.') }}</p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body pb-0">
        <ul class="nav nav-tabs nav-fill border-0" role="tablist">
            @foreach($groups as $key => $label)
                <li class="nav-item">
                    <a class="nav-link @if($activeTab === $key) active @endif" href="{{ route('website.dropdown_data.index', ['tab' => $key]) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

@foreach($groupItems as $type => $config)
    @include('admin.website_settings.dropdown_data._card', [
        'type' => $type,
        'config' => $config,
        'records' => $records[$type] ?? collect(),
        'searchValue' => $searches[$type] ?? null,
        'lookups' => $lookups,
    ])
@endforeach
@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    window.initDropdownDataLocationDependencies = function (root) {
        var $root = root ? $(root) : $(document);
        var stateRoute = '{{ route('states.get_state_by_country') }}';
        var cityRoute = '{{ route('cities.get_cities_by_state') }}';
        var csrf = '{{ csrf_token() }}';

        $root.find('select[data-location-role="country"]').each(function () {
            var $country = $(this);
            var $form = $country.closest('form');
            var $state = $form.find('select[data-location-role="state"]');
            var $city = $form.find('select[data-location-role="city"]');

            if (!$state.length) {
                return;
            }

            var loadCities = function (selectedCityId) {
                if (!$city.length) {
                    return;
                }

                var stateId = $state.val();
                $city.prop('disabled', true);
                $city.html('<option value="">{{ translate('Choose') }}</option>');
                $city.selectpicker('refresh');

                if (!stateId) {
                    return;
                }

                $.post(cityRoute, {_token: csrf, state_id: stateId}, function (data) {
                    $city.html('<option value="">{{ translate('Choose') }}</option>');
                    for (var i = 0; i < data.length; i++) {
                        $city.append($('<option>', {
                            value: data[i].id,
                            text: data[i].name,
                            selected: selectedCityId && String(selectedCityId) === String(data[i].id)
                        }));
                    }
                    $city.prop('disabled', false);
                    $city.selectpicker('refresh');
                }).fail(function () {
                    $city.html('<option value="">{{ translate('Choose') }}</option>');
                    $city.prop('disabled', false);
                    $city.selectpicker('refresh');
                });
            };

            var loadStates = function (selectedStateId, selectedCityId) {
                var countryId = $country.val();
                $state.prop('disabled', true);
                $state.html('<option value="">{{ translate('Choose') }}</option>');
                $state.selectpicker('refresh');

                if (!countryId) {
                    if ($city.length) {
                        $city.html('<option value="">{{ translate('Choose') }}</option>');
                        $city.selectpicker('refresh');
                    }
                    return;
                }

                $.post(stateRoute, {_token: csrf, country_id: countryId}, function (data) {
                    $state.html('<option value="">{{ translate('Choose') }}</option>');
                    for (var i = 0; i < data.length; i++) {
                        $state.append($('<option>', {
                            value: data[i].id,
                            text: data[i].name,
                            selected: selectedStateId && String(selectedStateId) === String(data[i].id)
                        }));
                    }
                    $state.prop('disabled', false);
                    $state.selectpicker('refresh');

                    if ($city.length) {
                        if (selectedStateId) {
                            loadCities(selectedCityId);
                        } else {
                            $city.html('<option value="">{{ translate('Choose') }}</option>');
                            $city.prop('disabled', false);
                            $city.selectpicker('refresh');
                        }
                    }
                }).fail(function () {
                    $state.html('<option value="">{{ translate('Choose') }}</option>');
                    $state.prop('disabled', false);
                    $state.selectpicker('refresh');
                });
            };

            $country.off('change.dropdownDataLocation changed.bs.select.dropdownDataLocation').on('change.dropdownDataLocation changed.bs.select.dropdownDataLocation', function () {
                loadStates($state.data('selected') || null, $city.data('selected') || null);
            });

            $state.off('change.dropdownDataLocation changed.bs.select.dropdownDataLocation').on('change.dropdownDataLocation changed.bs.select.dropdownDataLocation', function () {
                loadCities($city.data('selected') || null);
            });

            loadStates($state.data('selected') || null, $city.data('selected') || null);
        });
    };

    function dropdown_data_modal(url) {
        $.get(url, function (data) {
            $('.create_edit_modal_content').html(data);
            if (window.initDropdownDataLocationDependencies) {
                window.initDropdownDataLocationDependencies($('.create_edit_modal_content'));
            }
            $('.create_edit_modal').modal('show');
        });
    }

    $(document).ready(function () {
        if (window.initDropdownDataLocationDependencies) {
            window.initDropdownDataLocationDependencies($(document));
        }
    });
</script>
@endsection
