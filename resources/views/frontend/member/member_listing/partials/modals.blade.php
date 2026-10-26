
    @include('modals.package_update_alert_modal')
    @include('modals.confirm_modal')

    <!-- Ignore Modal -->
    <div class="modal fade ignore_member_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Ignore Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Are you sure that you want to ignore this member?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light round-btn" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="submit" class="btn btn-primary round-btn" id="ignore_button">{{ translate('Ignore') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Profile -->
    <div class="modal fade report_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Report Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('reportusers.store') }}" id="report-modal-form" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" id="member_id" value="">
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{ translate('Report Reason') }}<span
                                    class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <textarea name="reason" rows="4" class="form-control" placeholder="{{ translate('Report Reason') }}"
                                    required></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light round-btn" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary round-btn"
                        onclick="submitReport()">{{ translate('Report') }}</button>
                </div>
            </div>
        </div>
    </div>