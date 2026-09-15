{{--
    Manual-review gate popup (web).

    The app shows a full-screen ManualReviewView when the API answers 423; the
    website had no equivalent, so a member whose verification landed in manual
    review only saw an inline dashboard banner and never learned that the team
    would email them. This is that missing screen.

    Shown ONCE per login: the session is regenerated at login, so the
    `manual_review_popup_seen` flag below re-arms itself on every fresh sign-in
    and does not nag on every page view.
--}}
@auth
    @php
        $mrUser = auth()->user();
        $mrUnderReview = method_exists($mrUser, 'isUnderManualReview') && $mrUser->isUnderManualReview();
        $mrState = $mrUnderReview ? $mrUser->manualReviewState() : null;
        $mrFirstTimeThisLogin = $mrUnderReview && ! session('manual_review_popup_seen');
        if ($mrFirstTimeThisLogin) {
            session(['manual_review_popup_seen' => true]);
        }
    @endphp

    @if ($mrUnderReview)
        <div class="modal fade" id="manual-review-modal" tabindex="-1" role="dialog"
             data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i class="las la-user-shield" style="font-size: 54px; color: #f0a202;"></i>
                        </div>

                        <h5 class="fw-700 mb-2">{{ translate('You are under review') }}</h5>

                        <p class="mb-2">
                            {{ translate('Our team is reviewing your profile and documents. We will send the verification result to your registered email address.') }}
                        </p>

                        <p class="fs-13 opacity-70 mb-3">
                            <i class="las la-envelope"></i>
                            <strong>{{ $mrUser->email }}</strong>
                        </p>

                        @if (! empty($mrState['expired']))
                            <div class="alert alert-warning fs-13 text-left">
                                {{ translate('Your review window has passed. If you have not heard from us, please contact the Help Center so we can check your case.') }}
                            </div>
                        @endif

                        <div class="alert alert-light border fs-13 text-left mb-4">
                            {{ translate('Entered your information correctly but still not verified? Contact our Help Center and the team will re-check your case.') }}
                        </div>

                        <div class="d-flex flex-wrap justify-content-center">
                            <a href="{{ route('contact_us', [
                                    'category' => 'issue',
                                    'subject' => translate('Verification pending — please review my account'),
                                ]) }}"
                               class="btn btn-primary mr-2 mb-2">
                                <i class="las la-headset"></i>
                                {{ translate('Contact Help Center') }}
                            </a>

                            <button type="button" class="btn btn-soft-secondary mb-2" data-dismiss="modal">
                                {{ translate('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($mrFirstTimeThisLogin)
            <script>
                $(document).ready(function () {
                    $('#manual-review-modal').modal('show');
                });
            </script>
        @endif
    @endif
@endauth
