@extends('frontend.layouts.app')

@section('content')
@php
    $status = $verificationStatus['status'] ?? 'not_started';
    $attempts = collect($recentAttempts ?? []);
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
              <strong>{{ translate('Live verification log') }}</strong>
              <span id="ai-badge" class="badge badge-soft-warning">{{ strtoupper($status) }}</span>
            </div>
            <div id="ai-log-list" style="max-height:260px;overflow:auto;font-size:14px;line-height:1.6;">
              <div class="text-muted" id="ai-initial-log">{{ translate('Waiting to start...') }}</div>
              @foreach($attempts as $attempt)
                <div class="mt-2">
                  <div><strong>#{{ $attempt->id }}</strong> - {{ $attempt->status }}</div>
                  @if($attempt->recommendation)
                    <div class="text-success">{{ translate('Recommendation') }}: {{ $attempt->recommendation }}</div>
                  @endif
                  @if($attempt->error_message)
                    <div class="text-danger">{{ $attempt->error_message }}</div>
                  @endif
                </div>
              @endforeach
            </div>
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
    var logs = @json($verificationStatus['last_error'] ? [['level' => 'danger', 'message' => $verificationStatus['last_error']]] : []);
    var timer = 60;
    var timerText = document.getElementById('ai-timer-text');
    var dashBtn = document.getElementById('ai-dashboard-btn');
    var progress = document.getElementById('ai-progress');
    var logList = document.getElementById('ai-log-list');
    var badge = document.getElementById('ai-badge');
    var stateRunning = document.getElementById('ai-state-running');
    var stateDone = document.getElementById('ai-state-done');
    var stateError = document.getElementById('ai-state-error');

    function setState(id) {
        [stateRunning, stateDone, stateError].forEach(function (el) {
            if (el) el.classList.add('d-none');
        });
        if (id === 'running' && stateRunning) stateRunning.classList.remove('d-none');
        if (id === 'done' && stateDone) stateDone.classList.remove('d-none');
        if (id === 'error' && stateError) stateError.classList.remove('d-none');
    }

    function addLog(level, message) {
        if (!logList) return;
        var color = level === 'success' ? 'text-success' : (level === 'danger' ? 'text-danger' : (level === 'warning' ? 'text-warning' : 'text-muted'));
        var item = document.createElement('div');
        item.className = 'mb-1 ' + color;
        item.textContent = '• ' + message;
        logList.appendChild(item);
        logList.scrollTop = logList.scrollHeight;
    }

    addLog('info', 'Starting AI verification request...');
    (logs || []).forEach(function (entry) { addLog(entry.level || 'info', entry.message || ''); });
    addLog('info', 'Sending verification request to the API...');

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

    addLog('info', 'Verification API hit: ' + runUrl);

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
        addLog('success', 'Verification API responded successfully.');
        addLog('info', 'Response status: ' + (data.status || 'pending'));

        if (Array.isArray(data.logs)) {
            data.logs.forEach(function (entry) {
                addLog(entry.level || 'info', entry.message || '');
            });
        }

        if (!data.redirect) {
            addLog('danger', 'Missing redirect URL in response.');
            setState('error');
            return;
        }

        if (badge) {
            badge.textContent = (data.status || 'pending').toUpperCase();
            badge.className = 'badge ' + (data.verified ? 'badge-soft-success' : 'badge-soft-warning');
        }

        document.getElementById('ai-title').textContent = data.title || '';
        document.getElementById('ai-message').textContent = data.message || '';
        document.getElementById('ai-continue').setAttribute('href', data.redirect);

        if (data.verified) {
            setState('done');
        } else if (data.status === 'pending' || data.status === 'processing') {
            setState('running');
        } else {
            setState('error');
        }
    })
    .catch(function (error) {
        addLog('danger', error && error.message ? error.message : 'Verification request failed.');
        setState('error');
    });
})();
</script>
@endsection