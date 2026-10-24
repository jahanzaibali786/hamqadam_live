
<script>
(function(){const root=document.querySelector('.hq-ref24-discovery');if(!root)return;root.querySelectorAll('.hq24-view-toggle button').forEach(btn=>btn.addEventListener('click',()=>{root.classList.toggle('hq24-list-view',btn.dataset.cols==='one');root.querySelectorAll('.hq24-view-toggle button').forEach(x=>{x.classList.toggle('is-selected',x===btn);x.setAttribute('aria-pressed',String(x===btn));});}));})();
</script>

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