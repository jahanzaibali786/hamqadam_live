<style>
    .global-call-shell-avatar,
    .global-call-audio-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto;
        box-shadow: 0 0 0 8px rgba(255, 52, 132, 0.15), 0 16px 48px rgba(255, 52, 132, 0.24);
    }
    .global-call-shell-avatar img,
    .global-call-audio-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .global-call-shell {
        background: linear-gradient(180deg, #fff 0%, #fff7fb 100%);
    }
    .global-call-ringing-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #fff0f6;
        color: #ff2f84;
        font-weight: 600;
        position: relative;
        overflow: hidden;
    }
    .global-call-ringing-pill::before {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ff2f84;
        box-shadow: 0 0 0 0 rgba(255, 47, 132, 0.35);
        animation: globalCallPulse 1.4s infinite;
    }
    @keyframes globalCallPulse {
        0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 47, 132, 0.35); }
        70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(255, 47, 132, 0); }
        100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 47, 132, 0); }
    }
    .global-call-screen-modal-content,
    .global-call-screen {
        border-radius: 24px;
        overflow: hidden;
    }
    .global-call-screen {
        min-height: 72vh;
    }
    .global-call-video-area,
    .global-call-audio-area {
        position: absolute;
        inset: 0;
    }
    .global-call-audio-area {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: radial-gradient(circle at top, rgba(255,255,255,.08), rgba(0,0,0,.35));
    }
    .global-call-overlay-header {
        position: relative;
        z-index: 3;
        background: linear-gradient(180deg, rgba(0,0,0,.68), rgba(0,0,0,0));
    }
    .global-call-controls {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 4;
    }
    .global-call-controls .btn {
        width: 52px;
        height: 52px;
    }
    .global-local-video-preview {
        position: absolute;
        right: 18px;
        bottom: 98px;
        width: 140px;
        height: 190px;
        border-radius: 18px;
        overflow: hidden;
        border: 2px solid rgba(255,255,255,.6);
        box-shadow: 0 10px 30px rgba(0,0,0,.35);
        z-index: 3;
        background: #111;
    }
    .global-remote-video-screen {
        width: 100%;
        height: 100%;
        background: #111;
    }
</style>

<div class="modal fade" id="global-call-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 20px;">
            <div class="modal-body p-0">
                <div class="global-call-shell p-4 p-lg-5 text-center">
                    <div class="global-call-shell-avatar mx-auto mb-3">
                        <img id="global-call-shell-avatar" src="{{ static_asset('assets/img/avatar-place.png') }}" alt="avatar">
                    </div>
                    <h4 class="mb-1 fw-600" id="global-call-shell-name">{{ translate('Calling') }}</h4>
                    <p class="mb-3 text-muted" id="global-call-shell-status">{{ translate('Connecting...') }}</p>
                    <div class="mb-4" id="global-call-shell-meta"></div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" class="btn btn-soft-danger btn-lg px-4 d-none" id="global-call-shell-decline" onclick="declineIncomingCall()">{{ translate('Decline') }}</button>
                        <button type="button" class="btn btn-primary btn-lg px-4 d-none" id="global-call-shell-accept" onclick="acceptIncomingCall()">{{ translate('Accept') }}</button>
                        <button type="button" class="btn btn-warning btn-lg px-4 d-none" id="global-call-shell-cancel" onclick="cancelOutgoingCall()">{{ translate('Cancel Call') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="global-call-screen-modal" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content global-call-screen-modal-content border-0">
            <div class="modal-body p-0">
                <div class="global-call-screen bg-dark text-white position-relative overflow-hidden">
                    <div id="global-call-video-area" class="global-call-video-area d-none"></div>
                    <div id="global-call-audio-area" class="global-call-audio-area text-center p-4 p-lg-5"></div>
                    <div class="global-call-overlay-header d-flex justify-content-between align-items-center px-3 px-lg-4 py-3">
                        <div>
                            <h5 class="mb-0 fw-600" id="global-call-screen-name">{{ translate('Call') }}</h5>
                            <small class="text-white-50" id="global-call-screen-status">{{ translate('Connecting...') }}</small>
                        </div>
                        <div class="text-right">
                            <div class="call-timer fs-18 fw-600" id="global-call-screen-timer">00:00</div>
                        </div>
                    </div>
                    <div class="global-call-controls d-flex justify-content-center align-items-center flex-wrap gap-2 px-3 pb-4">
                        <button type="button" class="btn btn-icon btn-circle btn-light" id="global-call-toggle-mic" onclick="toggleCallMic()"><i class="las la-microphone"></i></button>
                        <button type="button" class="btn btn-icon btn-circle btn-light d-none" id="global-call-toggle-camera" onclick="toggleCallCamera()"><i class="las la-video"></i></button>
                        <button type="button" class="btn btn-icon btn-circle btn-light d-none" id="global-call-switch-camera" onclick="switchCallCamera()"><i class="las la-sync-alt"></i></button>
                        <button type="button" class="btn btn-danger btn-icon btn-circle" id="global-call-end-btn" onclick="endActiveCall()"><i class="las la-phone-slash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .global-call-controls {
        gap: 14px;
    }
    .global-call-controls .btn {
        margin: 0 4px;
    }
    .global-call-shell .btn-lg {
        min-width: 140px;
    }
    .global-call-shell .d-flex.flex-wrap {
        gap: 16px !important;
    }
</style>
