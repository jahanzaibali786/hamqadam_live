@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-four"><header class="hm-four-head"><div><h1><i class="las la-ring"></i> Proposals</h1><p>Take meaningful steps towards a stronger connection.</p></div><aside>A proposal can be the beginning of a beautiful journey.</aside></header><div class="hm-four-layout"><main><nav class="hm-four-tabs">@foreach([['received','Received'],['sent','Sent'],['accepted','Accepted'],['declined','Declined'],['in_progress','In Progress']] as $t)<a class="{{ $tab===$t[0]?'active':'' }}" href="{{ route('interest_requests',['tab'=>$t[0]]) }}">{{ $t[1] }} ({{ $counts[$t[0]] }})</a>@endforeach</nav><div class="hm-four-rows">@forelse($interests as $interest)@php($person=$interest->interested_by===auth()->id()?$interest->recipient:$interest->sender)@if($person)@php($fallback=static_asset(($person->member?->gender??1)==1?'assets/img/avatar-place.png':'assets/img/female-avatar-place.png'))@php($state=$interest->status instanceof \BackedEnum?$interest->status->value:(int)$interest->status)<article class="hm-four-request"><img src="{{ $person->photo&&show_profile_picture($person)?uploaded_asset($person->photo):$fallback }}" alt="{{ $person->first_name }}" onerror="this.onerror=null;this.src='{{ $fallback }}'"><div><h2>{{ $person->first_name }} {{ $person->last_name }}</h2><p>{{ $person->member?->birthday?\Carbon\Carbon::parse($person->member->birthday)->age.' years · ':'' }}{{ $person->addresses->firstWhere('type','present')?->city?->name??'Location unavailable' }}</p><small>{{ $person->member?->job_title??'Member' }}</small></div><blockquote>{{ $interest->initial_note?:'Proposal received through Hamqadam.' }}</blockquote><div class="hm-four-request-actions"><small>{{ $interest->created_at?->diffForHumans() }}</small><a class="hm-four-primary" href="{{ route('member_profile',$person->id) }}">View Details</a>@if($state===0 && $interest->user_id===auth()->id())<button onclick="accept_interest({{ $interest->id }})">✓ Accept</button><button class="outlined" onclick="reject_interest({{ $interest->id }})">✕ Decline</button>@else<span class="hm-four-state">{{ $interest->status instanceof \App\Enums\ProposalStatus?ucfirst($interest->status->label()):'Pending' }}</span>@endif</div></article>@endif@empty<p class="hm-four-empty">No proposals in this category.</p>@endforelse</div>{{ $interests->links() }}</main><aside><section class="hm-four-panel"><h2>Proposal Overview</h2><div class="hm-four-metrics">@foreach($counts as $key=>$value)<span><b>{{ $value }}</b><small>{{ ucfirst(str_replace('_',' ',$key)) }}</small></span>@endforeach</div></section><section class="hm-four-panel"><h2>Filters</h2><p>Select a proposal status above to narrow your list.</p><a href="{{ route('interest_requests') }}">Clear All →</a></section><section class="hm-four-promo"><a href="{{ route('member.reference.packages') }}">View Premium Plans →</a></section></aside></div></div>
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
