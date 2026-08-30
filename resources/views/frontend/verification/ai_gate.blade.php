@extends('frontend.layouts.app')

@section('content')
@php
    $status = $verificationStatus['status'] ?? 'not_started';
@endphp
<div class="py-5" style="min-height:70vh;background:linear-gradient(180deg,#fff 0%,#fff5f8 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-md-9">
        <div class="bg-white border rounded-xl shadow-sm p-4 p-md-5">
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:72px;height:72px;background:linear-gradient(135deg,#ff2d7a,#ff6b8d);box-shadow:0 16px 35px rgba(255,45,122,.25);">
              <i class="las la-shield-alt text-white" style="font-size:2rem"></i>
            </div>
            <h3 class="fw-700 mb-1" style="color:#ff2d7a">{{ translate('AI Verification') }}</h3>
            <p class="mb-0 text-muted">{{ translate('We are checking the identity evidence submitted during registration.') }}</p>
          </div>

          <div class="mb-4">
            <div class="progress" style="height:10px;background:#ffe1eb;">
              <div id="ai-progress" class="progress-bar" style="width:25%;background:linear-gradient(90deg,#ff6b8d,#ff2d7a);"></div>
            </div>
          </div>

          <div class="border rounded p-3 mb-4" style="background:#fffdfd;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <strong>{{ translate('Verification status') }}</strong>
              <span id="ai-badge" class="badge badge-soft-warning">{{ strtoupper($status) }}</span>
            </div>
            <div class="text-muted" id="ai-status-text">{{ translate('Waiting to start...') }}</div>
          </div>

          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="text-muted fs-13" id="ai-timer-text">{{ translate('You can return to the dashboard in 60 seconds.') }}</div>
            <div class="d-flex gap-2">
              <a id="ai-dashboard-btn" href="{{ route('dashboard') }}" class="btn btn-soft-primary d-none">{{ translate('Go to dashboard') }}</a>
            </div>
          </div>

          <div id="ai-state-running" class="mt-4 text-center">
            <div class="spinner-border" style="color:#ff2d7a" role="status"></div>
            <div class="mt-3 fw-600">{{ translate('Verification in progress') }}</div>
            <div class="text-muted fs-13">{{ translate('Please keep this page open while we process the request.') }}</div>
          </div>

          <div id="ai-state-done" class="mt-4 text-center d-none">
            <h5 id="ai-title" class="fw-700 mb-2"></h5>
            <p id="ai-message" class="text-muted mb-3"></p>
            <a id="ai-continue" href="{{ route('dashboard') }}" class="btn btn-primary">{{ translate('Continue') }}</a>
          </div>

          <div id="ai-state-error" class="mt-4 text-center d-none">
            <h5 class="fw-700 mb-2">{{ translate('We could not complete the verification') }}</h5>
            <p class="text-muted mb-3">{{ translate('Your account is created. You can complete or retry verification from your dashboard.') }}</p>
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
    var token = @json(csrf_token());
    var timer = 60;
    var timerText = document.getElementById('ai-timer-text');
    var dashBtn = document.getElementById('ai-dashboard-btn');
    var progress = document.getElementById('ai-progress');
    var statusText = document.getElementById('ai-status-text');
    var badge = document.getElementById('ai-badge');
    var stateRunning = document.getElementById('ai-state-running');
    var stateDone = document.getElementById('ai-state-done');
    var stateError = document.getElementById('ai-state-error');
    var title = document.getElementById('ai-title');
    var message = document.getElementById('ai-message');
    var continueBtn = document.getElementById('ai-continue');

    function setState(id) {
        [stateRunning, stateDone, stateError].forEach(function (el) {
            if (el) el.classList.add('d-none');
        });
        if (id === 'running' && stateRunning) stateRunning.classList.remove('d-none');
        if (id === 'done' && stateDone) stateDone.classList.remove('d-none');
        if (id === 'error' && stateError) stateError.classList.remove('d-none');
    }

    if (statusText) {
        statusText.textContent = '{{ translate('Starting verification...') }}';
    }

    var countdown = setInterval(function () {
        timer--;
        if (timerText) {
            timerText.textContent = timer > 0
                ? 'You can return to the dashboard in ' + timer + ' seconds.'
                : 'You can now return to the dashboard.';
        }
        if (progress) {
            var pct = Math.min(100, Math.max(25, ((60 - timer) / 60) * 100));
            progress.style.width = pct + '%';
        }
        if (timer <= 0) {
            clearInterval(countdown);
            if (dashBtn) dashBtn.classList.remove('d-none');
        }
    }, 1000);

    fetch(runUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (payload) {
        var data = payload.data || {};

        if (badge) {
            badge.textContent = (data.status || 'pending').toUpperCase();
            badge.className = 'badge ' + (data.verified ? 'badge-soft-success' : 'badge-soft-warning');
        }

        if (statusText) {
            statusText.textContent = data.message || '{{ translate('Verification request processed.') }}';
        }

        if (title) {
            title.textContent = data.title || '';
        }
        if (message) {
            message.textContent = data.message || '';
        }
        if (continueBtn) {
            continueBtn.setAttribute('href', data.redirect || '{{ route('dashboard') }}');
        }

        if (data.verified) {
            setState('done');
            window.setTimeout(function () {
                window.location.href = data.redirect || '{{ route('dashboard') }}';
            }, 900);
        } else if (data.status === 'pending' || data.status === 'processing') {
            setState('running');
        } else {
            setState('error');
        }
    })
    .catch(function (error) {
        if (statusText) {
            statusText.textContent = error && error.message ? error.message : '{{ translate('Verification request failed.') }}';
        }
        setState('error');
    });
})();
</script>
@endsection
