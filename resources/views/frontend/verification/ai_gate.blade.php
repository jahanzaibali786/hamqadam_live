@extends('layouts.app')

@section('content')
{{--
    Post-registration AI identity verification gate.

    The account already exists and the user is already logged in by the time
    they land here - registration is never blocked by the model. This screen
    only decides where they go next:

      APPROVE  -> dashboard
      anything else -> signed out and sent to login, still registered but
                       unverified, with the dashboard button available after
                       they log back in.

    The request is fired from JS rather than server-side so the user sees a
    progress state instead of a blank browser tab; the model needs several
    seconds on CPU for a CNIC + selfie comparison.
--}}
<div class="py-5" style="min-height:70vh">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="bg-white border rounded shadow-sm p-4 p-md-5 text-center">

          <div id="ai-state-running">
            <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem">
              <span class="sr-only">{{ translate('Verifying') }}</span>
            </div>
            <h4 class="fw-700 mb-2">{{ translate('Verifying your identity') }}</h4>
            <p class="opacity-70 mb-1">{{ translate('We are checking the documents you submitted during registration.') }}</p>
            <p class="fs-13 opacity-60 mb-0">{{ translate('This usually takes a few seconds. Please do not close this window.') }}</p>
          </div>

          <div id="ai-state-done" style="display:none">
            <div id="ai-icon" class="mb-3"></div>
            <h4 id="ai-title" class="fw-700 mb-2"></h4>
            <p id="ai-message" class="opacity-70 mb-3"></p>
            <a id="ai-continue" href="{{ route('dashboard') }}" class="btn btn-primary">{{ translate('Continue') }}</a>
          </div>

          <div id="ai-state-error" style="display:none">
            <h4 class="fw-700 mb-2">{{ translate('We could not complete the check') }}</h4>
            <p class="opacity-70 mb-3">{{ translate('Your account has been created. You can finish verification from your dashboard.') }}</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">{{ translate('Go to dashboard') }}</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var runUrl = @json(route('register.ai_verification.run'));
    var token  = @json(csrf_token());

    function show(id) {
        ['ai-state-running', 'ai-state-done', 'ai-state-error'].forEach(function (s) {
            var el = document.getElementById(s);
            if (el) { el.style.display = (s === id) ? 'block' : 'none'; }
        });
    }

    fetch(runUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (!data || !data.redirect) { show('ai-state-error'); return; }

        document.getElementById('ai-title').textContent = data.title || '';
        document.getElementById('ai-message').textContent = data.message || '';
        document.getElementById('ai-icon').innerHTML =
            '<i class="la ' + (data.verified ? 'la-check-circle text-success' : 'la-info-circle text-warning') + '" style="font-size:3rem"></i>';
        var cont = document.getElementById('ai-continue');
        cont.setAttribute('href', data.redirect);
        cont.textContent = data.cta || 'Continue';
        show('ai-state-done');

        // Give the member a moment to read the outcome before moving them on.
        setTimeout(function () { window.location.href = data.redirect; }, 2600);
    })
    .catch(function () { show('ai-state-error'); });
})();
</script>
@endsection
