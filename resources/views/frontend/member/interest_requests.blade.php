@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Interest Requests') }}</h5>
        </div>
        <div class="card-body">
            <div class="row gutters-10 align-items-center mb-3">
                <div class="col-md-4">
                    <label class="fs-12 fw-600 opacity-70 mb-1">{{ translate('Filter by Status') }}</label>
                    <select id="interest-status-filter" class="form-control">
                        <option value="">{{ translate('All Requests') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ translate('Pending') }}</option>
                        <option value="accepted" @selected(request('status') === 'accepted')>{{ translate('Accepted') }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ translate('Rejected') }}</option>
                        <option value="withdrawn" @selected(request('status') === 'withdrawn')>{{ translate('Withdrawn') }}</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>{{ translate('Cancelled') }}</option>
                        <option value="expired" @selected(request('status') === 'expired')>{{ translate('Expired') }}</option>
                    </select>
                </div>
            </div>

            <div id="interest-requests-table">
                @include('frontend.member.partials.interest_requests_table', ['interests' => $interests])
            </div>
        </div>
    </div>
@endsection
@section('modal')
    {{-- Interest Accept modal --}}
    <div class="modal fade interest_accept_modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Interest Accept!') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('accept_interest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="interest_id" id="interest_accept_id" value="">
                        <p class="mt-1">{{ translate('Are you sure you want to accept this interest?') }}</p>
                        <button type="button" class="action-btn btn btn-danger mt-2"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="action-btn btn btn-info mt-2">{{ translate('Confirm') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Interest Reject Modal --}}
    <div class="modal fade interest_reject_modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Interest Reject !') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('reject_interest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="interest_id" id="interest_reject_id" value="">
                        <p class="mt-1">{{ translate('Are you sure you want to rejet his interest?') }}</p>
                        <button type="button" class="btn btn-danger mt-2"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-info mt-2 action-btn">{{ translate('Confirm') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Interest Remove Modal --}}
    <div class="modal fade interest_remove_modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Remove Interest') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('remove_interest') }}" method="POST">
                        @csrf
                        <input type="hidden" name="interest_id" id="interest_remove_id" value="">
                        <p class="mt-1">{{ translate('Are you sure you want to remove this accepted interest?') }}</p>
                        <button type="button" class="btn btn-danger mt-2"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-info mt-2 action-btn">{{ translate('Confirm') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript">
        function accept_interest(id) {
            $('.interest_accept_modal').modal('show');
            $('#interest_accept_id').val(id);
        }

        function reject_interest(id) {
            $('.interest_reject_modal').modal('show');
            $('#interest_reject_id').val(id);
        }

        function remove_interest(id) {
            $('.interest_remove_modal').modal('show');
            $('#interest_remove_id').val(id);
        }

        var interestRequestXhr = null;

        function renderInterestRequestsTable(html) {
            $('#interest-requests-table').html(html || '');

            if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.fooTable === 'function') {
                AIZ.plugins.fooTable();
            }
        }

        function filterInterestRequests(status, url) {
            url = url || "{{ route('interest_requests') }}";

            if (interestRequestXhr) {
                interestRequestXhr.abort();
            }

            interestRequestXhr = $.ajax({
                url: url,
                type: 'GET',
                data: { status: status, partial: 1 },
                dataType: 'json',
                cache: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                beforeSend: function() {
                    $('#interest-requests-table').addClass('opacity-50');
                },
                success: function(response) {
                    renderInterestRequestsTable(response.html);
                    var newUrl = status ? url + '?status=' + encodeURIComponent(status) : url;
                    window.history.replaceState({}, '', newUrl);
                },
                error: function(xhr, textStatus) {
                    if (textStatus === 'abort') {
                        return;
                    }
                    AIZ.plugins.notify('danger', '{{ translate('Could not filter interest requests.') }}');
                    window.location.href = status ? url + '?status=' + encodeURIComponent(status) : url;
                },
                complete: function() {
                    interestRequestXhr = null;
                    $('#interest-requests-table').removeClass('opacity-50');
                }
            });
        }

        $(document).off('change.interestRequests', '#interest-status-filter')
            .on('change.interestRequests', '#interest-status-filter', function() {
            filterInterestRequests($(this).val());
        });

        $(document).on('click', '#interest-requests-table .pagination a', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            if (!href) {
                return;
            }

            $.ajax({
                url: href + (href.indexOf('?') === -1 ? '?' : '&') + 'partial=1',
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                beforeSend: function() {
                    $('#interest-requests-table').addClass('opacity-50');
                },
                success: function(response) {
                    renderInterestRequestsTable(response.html);
                    window.history.replaceState({}, '', href);
                },
                error: function() {
                    AIZ.plugins.notify('danger', '{{ translate('Could not load interest requests.') }}');
                },
                complete: function() {
                    $('#interest-requests-table').removeClass('opacity-50');
                }
            });
        });
        // Prevent submitting multiple button
        $('form').bind('submit', function(e) {
            if ($(".action-btn").attr('attempted') == 'true') {
                //stop submitting the form and disable the submit button.
                e.preventDefault();
                $(".action-btn").attr("disable", true);
            } else {
                $(".action-btn").attr("attempted", 'true');
            }
        });
    </script>
@endsection
