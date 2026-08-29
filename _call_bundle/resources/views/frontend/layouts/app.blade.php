@php
if (Session::has('locale')) {
    $locale = Session::get('locale', Config::get('app.locale'));
} else {
    $locale = env('DEFAULT_LANGUAGE');
}
$lang = \App\Models\Language::where('code', $locale)->first();
@endphp

<!DOCTYPE html>
@if (\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
    <html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@endif

<head>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">
    <!-- Title -->
    <title>@yield('meta_title', get_setting('website_name') . ' | ' . get_setting('site_motto'))</title>

    <!-- Required Meta Tags Always Come First -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="@yield('meta_description', get_setting('meta_description'))" />
    <meta name="keywords" content="@yield('meta_keywords', get_setting('meta_keywords'))">

    @yield('meta')

    @if (!isset($page))
        <!-- Schema.org markup for Google+ -->
        <meta itemprop="name" content="{{ config('app.name', env('APP_NAME')) }}">
        <meta itemprop="description" content="{{ get_setting('meta_description') }}">
        <meta itemprop="image" content="{{ uploaded_asset(get_setting('meta_image')) }}">

        <!-- Twitter Card data -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:site" content="@publisher_handle">
        <meta name="twitter:title" content="{{ config('app.name', env('APP_NAME')) }}">
        <meta name="twitter:description" content="{{ get_setting('meta_description') }}">
        <meta name="twitter:creator" content="@author_handle">
        <meta name="twitter:image" content="{{ uploaded_asset(get_setting('meta_image')) }}">

        <!-- Open Graph data -->
        <meta property="og:title" content="{{ config('app.name', env('APP_NAME')) }}" />
        <meta property="og:type" content="Business Site" />
        <meta property="og:url" content="{{ env('APP_URL') }}" />
        <meta property="og:image" content="{{ uploaded_asset(get_setting('meta_image')) }}" />
        <meta property="og:description" content="{{ get_setting('meta_description') }}" />
        <meta property="og:site_name" content="{{ get_setting('website_name') }}" />
        <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
    @endif

    <!-- Favicon -->
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">

    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap">
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css?v=') }}{{ rand(1000,9999) }}">

    @if (\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
        <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif

    <script>
        var AIZ = AIZ || {};
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #6d6e6f;
        }

        :root {
            --primary: {{ get_setting('base_color', '#FD2C79') }};
            --hov-primary: {{ get_setting('base_hov_color', '#0069d9') }};
            --soft-primary: {{ hex2rgba(get_setting('base_hov_color', '#377dff'), 0.15) }};
            --secondary: {{ get_setting('secondary_color', '#FD655B') }};
            --soft-secondary: {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 0.15) }};
        }

        .text-primary-grad {
            background: rgb(253, 41, 123);
            background: -moz-linear-gradient(0deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
            background: -webkit-linear-gradient(0deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
            background: linear-gradient(0deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-primary,
        .bg-primary-grad {
            background: rgb(253, 41, 123);
            background: -moz-linear-gradient(225deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
            background: -webkit-linear-gradient(225deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
            background: linear-gradient(225deg, {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }} 0%, {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }} 100%);
        }

        .fill-dark {
            fill: #4d4d4d;
        }

        .fill-primary-grad stop:nth-child(1) {
            stop-color: {{ hex2rgba(get_setting('secondary_color', '#FD655B'), 1) }};
        }

        .fill-primary-grad stop:nth-child(2) {
            stop-color: {{ hex2rgba(get_setting('base_color', '#FD2C79'), 1) }};
        }
    </style>

    @if (get_setting('google_analytics_activation') == 1)
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}"></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '{{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}');
        </script>
    @endif

    @if (get_setting('facebook_pixel_activation') == 1)
        <!-- Facebook Pixel Code -->
        <script>
            ! function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', {{ env('FACEBOOK_PIXEL_ID') }});
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ env('FACEBOOK_PIXEL_ID') }}/&ev=PageView&noscript=1" />
        </noscript>
        <!-- End Facebook Pixel Code -->
    @endif

    {!! get_setting('header_script') !!}

</head>

<body class="text-left">

    <div
        class="aiz-main-wrapper d-flex flex-column position-relative @if (Route::currentRouteName() != 'home') pt-8 pt-lg-10 @endif bg-white">

        @include('frontend.inc.header')

        @yield('content')

        @include('frontend.inc.footer')
    </div>

    @if (get_setting('show_cookies_agreement') == 'on')
        <div class="aiz-cookie-alert shadow-xl">
            <div class="p-3 bg-dark rounded">
                <div class="text-white mb-3">
                    {{strip_tags(get_setting('cookies_agreement_text')) }}
                </div>
                <button class="btn btn-primary aiz-cookie-accepet">
                    {{ translate('Ok. I Understood') }}
                </button>
            </div>
        </div>
    @endif

    @yield('modal')

    @include('frontend.partials.global_call_modals')

    <div class="modal fade account_status_change_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('member.account_deactivation') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="deacticvation_status" id="deacticvation_status" value="">
                        <h4 class="modal-title h6 mb-3" id="confirmation_note" value=""></h4>
                        <hr>
                        <button type="submit" class="btn btn-primary mt-2">{{ translate('Yes') }}</button>
                        <button type="button" class="btn btn-danger mt-2"
                            data-dismiss="modal">{{ translate('No') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade account_delete_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <form class="form-horizontal member-block" action="{{ route('member.account_delete') }}"
                        method="POST">
                        @csrf                      
                        <h4 class="modal-title h6 mb-3" id="delete_confirmation_note" value=""></h4>
                        <hr>
                        <button type="submit" class="btn btn-primary mt-2">{{ translate('Yes') }}</button>
                        <button type="button" class="btn btn-danger mt-2"
                            data-dismiss="modal">{{ translate('No') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (get_setting('facebook_chat_activation') == 1)
        <script type="text/javascript">
            window.fbAsyncInit = function() {
                FB.init({
                    xfbml: true,
                    version: 'v3.3'
                });
            };

            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s);
                js.id = id;
                js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        <div id="fb-root"></div>
        <!-- Your customer chat code -->
        <div class="fb-customerchat" attribution=setup_tool page_id="{{ env('FACEBOOK_PAGE_ID') }}">
        </div>
    @endif

    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    @if (get_setting('agora_calling_enabled') == 1)
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.24.0.js"></script>
    @endif
    <script>
        (function () {
            if (typeof window.Pusher === 'undefined') {
                return;
            }            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var pusherOptions = {
                cluster: '{{ get_setting('pusher_app_cluster', env('PUSHER_APP_CLUSTER')) }}',
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '{{ url('/broadcasting/auth') }}',
                authTransport: 'ajax',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                },
                channelAuthorization: {
                    endpoint: '{{ url('/broadcasting/auth') }}',
                    transport: 'ajax',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }
            };

            Pusher.logToConsole = true;
            var pusherClient = null;
            if ('{{ get_setting('chat_realtime_enabled') }}' == 1 && '{{ get_setting('pusher_app_key', env('PUSHER_APP_KEY')) }}' !== '') {
                pusherClient = new window.Pusher('{{ get_setting('pusher_app_key', env('PUSHER_APP_KEY')) }}', pusherOptions);
            }

            window.Echo = {
                private: function (channelName) {
                    if (!pusherClient) {
                        return { listen: function () { return this; } };
                    }

                    var channel = pusherClient.subscribe('private-' + channelName);
                    channel.bind('pusher:subscription_succeeded', function () {
                    });
                    channel.bind('pusher:subscription_error', function (status) {
                    });
                    channel.bind('pusher:member_added', function (member) {
                    });
                    channel.bind('pusher:member_removed', function (member) {
                    });
                    return {
                        listen: function (eventName, callback) {
                            channel.bind(eventName.replace(/^\./, ''), callback);
                            return this;
                        }
                    };
                },
                leave: function (channelName) {
                    if (pusherClient) {
                        pusherClient.unsubscribe('private-' + channelName);
                    }
                }
            };
        })();
    </script>
    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.js') }}"></script>

    @if (get_setting('firebase_push_notification') == 1)
        {{-- fcm --}}
        <!-- The core Firebase JS SDK is always required and must be listed first -->
        <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js"></script>

        <!-- TODO: Add SDKs for Firebase products that you want to use
        https://firebase.google.com/docs/web/setup#available-libraries -->

        <script>
            // Your web app's Firebase configuration
            var firebaseConfig = {
                apiKey: "{{ env('FCM_API_KEY') }}",
                authDomain: "{{ env('FCM_AUTH_DOMAIN') }}",
                projectId: "{{ env('FCM_PROJECT_ID') }}",
                storageBucket: "{{ env('FCM_STORAGE_BUCKET') }}",
                messagingSenderId: "{{ env('FCM_MESSAGING_SENDER_ID') }}",
                appId: "{{ env('FCM_APP_ID') }}",
            };

            // Initialize Firebase
            firebase.initializeApp(firebaseConfig);

            const messaging = firebase.messaging();

            function initFirebaseMessagingRegistration() {
                messaging.requestPermission()
                .then(function() {
                    return messaging.getToken()
                }).then(function(token) {
                    
                    $.ajax({
                        url: '{{ route('fcmToken') }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            fcm_token: token
                        },
                        dataType: 'JSON',
                        success: function (response) {
                            
                        },
                        error: function (err) {
                        },
                    });

                }).catch(function(err) {
                });
            }

            initFirebaseMessagingRegistration();        

            messaging.onMessage(function({
                data: {
                    body,
                    title
                }
            }) {
                new Notification(title, {
                    body
                });
            });
        </script>
        {{-- End of fcm --}}
    @endif

    @yield('script')

    @if (Auth::check() && Auth::user()->user_type == 'member')
    <script type="text/javascript">
        (function (window, $) {
            if (typeof window.startCall !== 'function') {
                window.startCall = function (type) {
                    var threadId = $('#chat_thread_id').val();
                    if (!threadId) { return; }
                    $.ajax({
                        url: "{{ route('chat.call.start') }}",
                        method: 'POST',
                        dataType: 'json',
                        headers: { 'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content') },
                        data: { chat_thread_id: threadId, call_type: type },
                        success: function (response) {
                            if (!response || !response.success) { return; }
                            if (typeof window.showOutgoingCallModal === 'function') { window.showOutgoingCallModal(response.data.call); }
                            if (typeof window.startIncomingCallCountdown === 'function') { window.startIncomingCallCountdown(response.data.call); }
                            if (typeof window.joinAgoraCall === 'function') { window.joinAgoraCall(response.data.call, response.data.rtc, true); }
                        }
                    });
                };
            }
            if (typeof window.showOutgoingCallModal !== 'function') { window.showOutgoingCallModal = function (call) { $('#global-call-shell-avatar').attr('src', call && call.receiver && call.receiver.photo ? call.receiver.photo : $('#global-call-shell-avatar').attr('src')); $('#global-call-shell-name').text(call && call.receiver && call.receiver.name ? call.receiver.name : 'Calling'); $('#global-call-shell-status').text('Calling...'); $('#global-call-shell-meta').html('<span class="badge badge-light">' + ((call && call.call_type) || 'audio') + '</span>'); $('#global-call-shell-accept').addClass('d-none'); $('#global-call-shell-decline').addClass('d-none'); $('#global-call-shell-cancel').removeClass('d-none'); window.currentCallState = call; window.currentCallId = call && call.id ? call.id : null; $('#global-call-action-modal').attr('data-call-id', window.currentCallId); $('#global-call-action-modal').modal('show'); }; }
            if (typeof window.showIncomingCallModal !== 'function') { window.showIncomingCallModal = function (call) { $('#global-call-shell-avatar').attr('src', call && call.caller && call.caller.photo ? call.caller.photo : $('#global-call-shell-avatar').attr('src')); $('#global-call-shell-name').text(call && call.caller && call.caller.name ? call.caller.name : 'Incoming Call'); $('#global-call-shell-status').text(call && call.call_type === 'video' ? 'Incoming Video Call' : 'Incoming Audio Call'); $('#global-call-shell-meta').html('<div class="call-ringing-pill">Ringing</div>'); $('#global-call-shell-accept').removeClass('d-none').data('call-id', call ? call.id : null); $('#global-call-shell-decline').removeClass('d-none').data('call-id', call ? call.id : null); $('#global-call-shell-cancel').addClass('d-none'); window.currentCallState = call; window.currentCallId = call && call.id ? call.id : null; $('#global-call-action-modal').attr('data-call-id', window.currentCallId); $('#global-call-action-modal').modal('show'); }; }
            if (typeof window.startIncomingCallCountdown !== 'function') { window.startIncomingCallCountdown = function (call) { window.currentCallTimer = setTimeout(function () { if (window.currentCallState && call && window.currentCallState.id === call.id && typeof window.endActiveCall === 'function') { window.endActiveCall('missed'); } }, 30000); }; }
            if (typeof window.joinAgoraCall !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.stopCallSession !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.appendCallTimelineEntry !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.acceptIncomingCall !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.declineIncomingCall !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.cancelOutgoingCall !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.endActiveCall !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.toggleCallMic !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.toggleCallCamera !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.switchCallCamera !== 'function') { /* global call bootstrap defines this later */ }
            if (typeof window.handleIncomingCallSignal !== 'function') { window.handleIncomingCallSignal = function (event) { if (window.showIncomingCallModal) { var call = event && event.call ? event.call : event; if (call && call.id) { window.showIncomingCallModal(call); } } }; }
            if (typeof window.handleCallSignal !== 'function') { /* global call bootstrap defines this later */ }
        })(window, window.jQuery);
    </script>
    @endif


    <script type="text/javascript">
        (function (window, $) {
            if (typeof window.acceptIncomingCall !== 'function') {
                window.currentCallState = window.currentCallState || null;
                window.currentUserId = {{ Auth::id() }};
                window.currentCallId = window.currentCallId || null;
                window.currentCallTracks = window.currentCallTracks || [];
                window.currentCallClient = window.currentCallClient || null;
                window.currentCallTimer = window.currentCallTimer || null;
                window.currentCallDurationTimer = window.currentCallDurationTimer || null;
                window.currentCallStartedAt = window.currentCallStartedAt || null;

                function modalRoot() {
                    return $('#global-call-action-modal');
                }

                function getCallCounterpart(call) {
                    var userId = parseInt(window.currentUserId || {{ Auth::id() }}, 10);
                    var callerId = call && call.caller && call.caller.id ? parseInt(call.caller.id, 10) : null;
                    var receiverId = call && call.receiver && call.receiver.id ? parseInt(call.receiver.id, 10) : null;
                    if (callerId && userId && callerId === userId) {
                        return call.receiver || null;
                    }
                    if (receiverId && userId && receiverId === userId) {
                        return call.caller || null;
                    }
                    return call.receiver || call.caller || null;
                }

                function screenRoot() {
                    return $('#global-call-screen-modal');
                }

                function callRequest(url, data, onSuccess) {
                    return $.ajax({
                        url: url,
                        method: 'POST',
                        data: Object.assign({_token: $('meta[name="csrf-token"]').attr('content')}, data || {}),
                        success: onSuccess,
                        error: function (xhr) {
                            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Calling service unavailable.';
                            if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.notify === 'function') {
                                AIZ.plugins.notify('danger', message);
                            }
                        }
                    });
                }

                window.showOutgoingCallModal = function (call) {
                    window.currentCallState = call;
                    window.currentCallId = call && call.id ? call.id : null;
                    modalRoot().attr('data-call-id', window.currentCallId);
                    var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
                    $('#global-call-shell-avatar').attr('src', peer.photo ? peer.photo : '{{ static_asset('assets/img/avatar-place.png') }}');
                    $('#global-call-shell-name').text(peer.name ? peer.name : 'Calling');
                    $('#global-call-shell-status').text('Calling...');
                    $('#global-call-shell-meta').html('<span class="badge badge-light">' + ((call && call.call_type) || 'audio') + '</span>');
                    $('#global-call-shell-accept').addClass('d-none').attr('data-call-id', '');
                    $('#global-call-shell-decline').addClass('d-none').attr('data-call-id', '');
                    $('#global-call-shell-cancel').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
                    modalRoot().modal('show');
                };

                window.showIncomingCallModal = function (call) {
                    window.currentCallState = call;
                    window.currentCallId = call && call.id ? call.id : null;
                    modalRoot().attr('data-call-id', window.currentCallId);
                    var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
                    $('#global-call-shell-avatar').attr('src', peer.photo ? peer.photo : '{{ static_asset('assets/img/avatar-place.png') }}');
                    $('#global-call-shell-name').text(peer.name ? peer.name : 'Incoming Call');
                    $('#global-call-shell-status').text(call && call.call_type === 'video' ? 'Incoming Video Call' : 'Incoming Audio Call');
                    $('#global-call-shell-meta').html('<div class="global-call-ringing-pill">Ringing</div>');
                    $('#global-call-shell-accept').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
                    $('#global-call-shell-decline').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
                    $('#global-call-shell-cancel').addClass('d-none').attr('data-call-id', '');
                    modalRoot().modal('show');
                };

                window.startIncomingCallCountdown = function (call) {
                    clearTimeout(window.currentCallTimer);
                    window.currentCallTimer = setTimeout(function () {
                        if (window.currentCallState && call && window.currentCallState.id === call.id && typeof window.endActiveCall === 'function') {
                            window.endActiveCall('missed');
                        }
                    }, 30000);
                };

                window.openActiveCallScreen = function (call) {
                    window.currentCallState = call;
                    window.currentCallId = call && call.id ? call.id : null;
                    screenRoot().attr('data-call-id', window.currentCallId);
                    var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
                    $('#global-call-screen-name').text(peer.name ? peer.name : 'Call');
                    $('#global-call-screen-status').text('Connected');
                    $('#global-call-screen-timer').text('00:00');
                    if (call && call.call_type === 'video') {
                        $('#global-call-video-area').removeClass('d-none');
                        $('#global-call-audio-area').addClass('d-none');
                        $('#global-call-toggle-camera, #global-call-switch-camera').removeClass('d-none');
                    } else {
                        $('#global-call-video-area').addClass('d-none');
                        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
                        $('#global-call-audio-area').removeClass('d-none').html('<div class="global-call-audio-avatar mb-3"><img src="' + (peer.photo ? peer.photo : '{{ static_asset('assets/img/avatar-place.png') }}') + '" alt="avatar"></div><h3 class="mb-0">' + (peer.name ? peer.name : '') + '</h3><p class="mb-0 text-white-50">Connected</p>');
                        $('#global-call-toggle-camera, #global-call-switch-camera').addClass('d-none');
                    }
                    screenRoot().modal('show');
                };

                window.startCallTimer = function () {
                    window.currentCallStartedAt = new Date();
                    clearInterval(window.currentCallDurationTimer);
                    window.currentCallDurationTimer = setInterval(function () {
                        if (!window.currentCallStartedAt) { return; }
                        var diff = Math.max(0, Math.floor((Date.now() - window.currentCallStartedAt.getTime()) / 1000));
                        var mm = String(Math.floor(diff / 60)).padStart(2, '0');
                        var ss = String(diff % 60).padStart(2, '0');
                        $('#global-call-screen-timer').text(mm + ':' + ss);
                    }, 1000);
                };

                window.joinAgoraCall = async function (call, rtc, isCaller) {
                    if (typeof AgoraRTC === 'undefined' || !rtc || !rtc.token) {
                        if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.notify === 'function') {
                            AIZ.plugins.notify('danger', 'Calling service unavailable.');
                        }
                        return;
                    }
                    if (window.currentCallClient || (window.currentCallTracks && window.currentCallTracks.length)) {
                        window.stopCallSession && window.stopCallSession(false);
                    }
                    window.currentCallClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    window.currentCallClient.on('user-published', async function (user, mediaType) {
                        await window.currentCallClient.subscribe(user, mediaType);
                        if (mediaType === 'video') {
                            $('#global-call-video-area').removeClass('d-none').html('<div id="global-remote-video-area" class="global-remote-video-screen"></div><div id="global-local-video-preview" class="global-local-video-preview"></div>');
                            if (user.videoTrack) { user.videoTrack.play('global-remote-video-area'); }
                        }
                        if (mediaType === 'audio' && user.audioTrack) {
                            user.audioTrack.play();
                        }
                    });
                    window.currentCallClient.on('user-left', function () {
                        if (typeof checkUnreadChats === 'function') { checkUnreadChats(); }
                    });
                    try {
                        var tracks = call.call_type === 'video' ? await AgoraRTC.createMicrophoneAndCameraTracks() : [await AgoraRTC.createMicrophoneAudioTrack()];
                        window.currentCallTracks = tracks;
                        if (call.call_type === 'video' && tracks[1]) {
                            $('#global-call-video-area').removeClass('d-none').html('<div id="global-remote-video-area" class="global-remote-video-screen"></div><div id="global-local-video-preview" class="global-local-video-preview"></div>');
                            tracks[1].play('global-local-video-preview');
                        }
                        await window.currentCallClient.join(rtc.app_id, rtc.channel, rtc.token, rtc.uid);
                        await window.currentCallClient.publish(tracks);
                        callRequest('{{ route('chat.call.connect', ['call' => '__CALL__']) }}'.replace('__CALL__', call.id), {}, function () {});
                        window.startCallTimer();
                    } catch (error) {
                        console.error('[global call] join failed', error);
                        if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.notify === 'function') {
                            AIZ.plugins.notify('danger', 'Unable to access microphone or camera');
                        }
                        window.endActiveCall('failed');
                    }
                };

                window.stopCallSession = function (closeModal) {
                    clearTimeout(window.currentCallTimer);
                    clearInterval(window.currentCallDurationTimer);
                    window.currentCallTimer = null;
                    window.currentCallDurationTimer = null;
                    window.currentCallStartedAt = null;
                    if (window.currentCallTracks && window.currentCallTracks.length) {
                        window.currentCallTracks.forEach(function (track) {
                            if (track && track.close) { track.close(); }
                        });
                    }
                    window.currentCallTracks = [];
                    if (window.currentCallClient) {
                        window.currentCallClient.leave().catch(function () {});
                    }
                    window.currentCallClient = null;
                    if (closeModal !== false) {
                        $('#global-call-screen-modal').modal('hide');
                        $('#global-call-action-modal').modal('hide');
                    }
                };

                window.acceptIncomingCall = function () {
                    var callId = window.currentCallId || $('#global-call-action-modal').attr('data-call-id') || $('#global-call-shell-accept').attr('data-call-id');
                    if (!callId) { return; }
                    console.log('[global call] acceptIncomingCall', callId);
                    callRequest('{{ route('chat.call.accept', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function (response) {
                        if (!response || !response.success) {
                            return;
                        }
                        $('#global-call-action-modal').modal('hide');
                        window.currentCallState = response.data.call;
                        window.currentCallState.rtc = response.data.rtc;
                        window.joinAgoraCall(window.currentCallState, response.data.rtc, false);
                        if (typeof window.openActiveCallScreen === 'function') {
                            window.openActiveCallScreen(window.currentCallState);
                        }
                    });
                };

                window.declineIncomingCall = function () {
                    var callId = window.currentCallId || $('#global-call-action-modal').attr('data-call-id') || $('#global-call-shell-decline').attr('data-call-id');
                    if (!callId) { return; }
                    console.log('[global call] declineIncomingCall', callId);
                    callRequest('{{ route('chat.call.reject', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function (response) {
                        $('#global-call-action-modal').modal('hide');
                        window.stopCallSession();
                        if (response && response.data && response.data.call && typeof window.appendCallTimelineEntry === 'function') {
                            window.appendCallTimelineEntry(response.data.call);
                        }
                    });
                };

                window.cancelOutgoingCall = function () {
                    var callId = window.currentCallId || $('#global-call-action-modal').attr('data-call-id') || $('#global-call-shell-cancel').attr('data-call-id');
                    if (!callId) { return; }
                    console.log('[global call] cancelOutgoingCall', callId);
                    callRequest('{{ route('chat.call.cancel', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function (response) {
                        $('#global-call-action-modal').modal('hide');
                        window.stopCallSession();
                        if (response && response.data && response.data.call && typeof window.appendCallTimelineEntry === 'function') {
                            window.appendCallTimelineEntry(response.data.call);
                        }
                    });
                };

                window.endActiveCall = function (status) {
                    var callId = window.currentCallId || $('#global-call-action-modal').attr('data-call-id') || $('#global-call-screen-modal').attr('data-call-id');
                    if (!callId) { return; }
                    console.log('[global call] endActiveCall', callId, status || 'ended');
                    callRequest('{{ route('chat.call.end', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), { status: status || 'ended' }, function (response) {
                        $('#global-call-screen-modal').modal('hide');
                        window.stopCallSession();
                        if (response && response.data && response.data.call && typeof window.appendCallTimelineEntry === 'function') {
                            window.appendCallTimelineEntry(response.data.call);
                        }
                    });
                };

                window.toggleCallMic = function () {
                    var track = (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'audio'; });
                    if (track && track.setEnabled) { track.setEnabled(!track.enabled); }
                };

                window.toggleCallCamera = function () {
                    var track = (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'video'; });
                    if (track && track.setEnabled) { track.setEnabled(!track.enabled); }
                };

                window.switchCallCamera = function () {
                    var track = (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'video'; });
                    if (!track || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return; }
                    navigator.mediaDevices.enumerateDevices().then(function (devices) {
                        var cameras = devices.filter(function (device) { return device.kind === 'videoinput'; });
                        if (cameras.length > 1 && track.setDevice) {
                            track.setDevice(cameras[1].deviceId);
                        }
                    });
                };

                window.appendCallTimelineEntry = function () {};

                window.handleIncomingCallSignal = function (event) {
                    var call = event && event.call ? event.call : event;
                    if (call && call.id) {
                        window.showIncomingCallModal(call);
                    }
                };

                window.handleCallSignal = function (status, event) {
                    var call = event && event.call ? event.call : event;
                    if (!call || !call.id) { return; }
                    if (status === 'accepted') {
                        $('#global-call-action-modal').modal('hide');
                        window.currentCallState = call;
                        window.currentCallId = call.id;
                        if (typeof window.openActiveCallScreen === 'function') {
                            window.openActiveCallScreen(call);
                        }
                        return;
                    }
                    if (['rejected', 'cancelled', 'busy', 'ended', 'missed'].indexOf(status) !== -1) {
                        $('#global-call-action-modal').modal('hide');
                        window.stopCallSession();
                    }
                };
            }
        })(window, window.jQuery);
    </script>

    @if (Auth::check() && Auth::user()->user_type == 'member')
    <script type="text/javascript">
        var lastUnreadCount = 0;
        function updateChatBadges(count) {
            var sidebarBadge = $('.unseen-chat-badge-sidebar');
            if (count > 0) {
                sidebarBadge.text(count).show();
            } else {
                sidebarBadge.hide();
            }
            var headerBadge = $('.chat-header-badge');
            headerBadge.toggle(count > 0);
            var footerBadge = $('.chat-footer-badge');
            if (footerBadge.length) {
                if (count > 0) {
                    footerBadge.text(count).show();
                } else {
                    footerBadge.hide();
                }
            }
        }
        function checkUnreadChats() {
            $.get('{{ route('chat.unread_count') }}', {
                active_thread_id: window.activeChatThreadId
                    || $('.chat-user-item.selected-chat').first().data('thread-id')
                    || ($('#chat_thread_id').length ? $('#chat_thread_id').val() : 0)
            }, function(data) {
                var count = parseInt(data.count);
                console.log('[chat badge] unread_count response', data);
                console.log('[chat badge] unread count parsed', count, 'previous', lastUnreadCount);
                updateChatBadges(count);
                if (count > lastUnreadCount && !window.location.pathname.endsWith('/chat')) {
                    var msg = data.sender_name ? data.sender_name + ': ' + data.message : '{{ translate('You have a new message') }}';
                    AIZ.plugins.notify('info', msg);
                }
                lastUnreadCount = count;
            });
        }
        $(document).ready(function() {
            $.get('{{ route('chat.unread_count') }}', {
                active_thread_id: window.activeChatThreadId
                    || $('.chat-user-item.selected-chat').first().data('thread-id')
                    || ($('#chat_thread_id').length ? $('#chat_thread_id').val() : 0)
            }, function(data) {
                lastUnreadCount = parseInt(data.count);
                updateChatBadges(lastUnreadCount);
            });

            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('App.User.{{ Auth::id() }}')
                    .listen('.message-sent', function (event) {
                        console.log('[chat badge] message-sent event', event);
                        var activeThreadId = window.activeChatThreadId ? parseInt(window.activeChatThreadId) : (($(".chat-user-item.selected-chat").first().data("thread-id")) ? parseInt($(".chat-user-item.selected-chat").first().data("thread-id")) : (($("#chat_thread_id").length) ? parseInt($("#chat_thread_id").val()) : null));
                        var incomingThreadId = event && event.thread_id ? parseInt(event.thread_id) : null;
                        console.log('[chat badge] active thread', activeThreadId, 'incoming thread', incomingThreadId);
                                                if (activeThreadId && incomingThreadId && activeThreadId === incomingThreadId) {
                            console.log('[chat badge] active thread match, skipping badge increment');
                            if (typeof window.markActiveChatThreadSeen === 'function') {
                                window.markActiveChatThreadSeen(function () {
                                    checkUnreadChats();
                                    if (window.location.pathname.endsWith('/chat') && typeof window.initChatRealtimeRefresh === 'function') {
                                        window.initChatRealtimeRefresh();
                                    }
                                });
                            } else {
                                checkUnreadChats();
                                if (window.location.pathname.endsWith('/chat') && typeof window.initChatRealtimeRefresh === 'function') {
                                    window.initChatRealtimeRefresh();
                                }
                            }
                            return;
                        }
                        if (typeof window.handleIncomingChatSidebarEvent === 'function') {
                            window.handleIncomingChatSidebarEvent(event);
                        }
                        console.log('[chat badge] unread before increment', lastUnreadCount);
                        lastUnreadCount = Math.max(0, lastUnreadCount + 1);
                        console.log('[chat badge] unread after increment', lastUnreadCount);
                        updateChatBadges(lastUnreadCount);
                        checkUnreadChats();
                        if (window.location.pathname.endsWith('/chat') && typeof window.initChatRealtimeRefresh === 'function') {
                            window.initChatRealtimeRefresh();
                        }
                    })
                    .listen('.message-read', function (event) {
                        console.log('[chat badge] message-read event', event);
                        checkUnreadChats();
                    })
                    .listen('.call-incoming', function (event) {
                        var call = event && event.call ? event.call : event;
                        if (typeof Notification !== 'undefined' && Notification.permission === 'granted' && call) {
                            try {
                                new Notification('{{ translate('Incoming call from Hamqadam') }}', {
                                    body: (call.caller && call.caller.name ? call.caller.name : '{{ translate('Someone is calling you') }}') + ' - ' + (call.call_type === 'video' ? '{{ translate('Video call') }}' : '{{ translate('Audio call') }}'),
                                    icon: call.caller && call.caller.photo ? call.caller.photo : '{{ static_asset('assets/img/avatar-place.png') }}'
                                });
                            } catch (e) {}
                        }
                        if (typeof window.handleIncomingCallSignal === 'function') {
                            window.handleIncomingCallSignal(event);
                        } else if (typeof window.showIncomingCallModal === 'function') {
                            window.showIncomingCallModal(call);
                        }
                    })
                    .listen('.call-accepted', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('accepted', event);
                        }
                    })
                    .listen('.call-rejected', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('rejected', event);
                        }
                    })
                    .listen('.call-cancelled', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('cancelled', event);
                        }
                    })
                    .listen('.call-busy', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('busy', event);
                        }
                    })
                    .listen('.call-ended', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('ended', event);
                        }
                    })
                    .listen('.call-missed', function (event) {
                        if (typeof window.handleCallSignal === 'function') {
                            window.handleCallSignal('missed', event);
                        }
                    });
            }
        });
    </script>
    @endif

    <script type="text/javascript">
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach

        @if (Auth::check() && Auth::user()->user_type == 'member')
            function account_deactivation() {
                var status = {{ Auth::user()->deactivated }}
                $('.account_status_change_modal').modal('show');
                if (status == 0) {
                    $('#deacticvation_status').val(1);
                    $('#confirmation_note').html('{{ translate('Deactivating your account will prevent you from performing any actions. Are you sure you want to deactivate your account?') }}');
                } else {
                    $('#deacticvation_status').val(0);
                    $('#confirmation_note').html('{{ translate('Are You Sure To Reactive Your Account') }}');
                }
            }
        @endif
        @if (Auth::check() && Auth::user()->user_type == 'member')
            function account_delete() {
                var status = {{ Auth::user()->deactivated }}
                $('.account_delete_modal').modal('show');
                    $('#delete_confirmation_note').html('{{ translate('Do You Really Want To Delete Your Account') }}');
            }
        @endif
    </script>


    @if (env('DEMO_MODE') == 'On')
        <script type="text/javascript">
            // Login credentials autoFill for demo
            function autoFill1() {
                $('#email').val('user2@example.com');
                $('#password').val('12345678');
            }

            function autoFill2() {
                $('#email').val('user17@example.com');
                $('#password').val('12345678');
            }
        </script>
    @endif

    {!! get_setting('footer_script') !!}

<script type="text/javascript">
(function (window, $) {
    if (window.__hamqadamGlobalCallUiPatched) { return; }
    window.__hamqadamGlobalCallUiPatched = true;

    window.currentCallPeer = window.currentCallPeer || null;
    window.currentCallAudioTrack = window.currentCallAudioTrack || null;
    window.currentCallVideoTrack = window.currentCallVideoTrack || null;
    window.currentCallMicMuted = window.currentCallMicMuted || false;
    window.currentCallCameraMuted = window.currentCallCameraMuted || false;
    window.currentCallToneState = window.currentCallToneState || null;

    function avatarFallback() {
        return '{{ static_asset('assets/img/avatar-place.png') }}';
    }

    function getCallCounterpart(call) {
        var userId = parseInt(window.currentUserId || {{ Auth::id() }}, 10);
        if (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) { return call.peer; }
        var caller = call && call.caller ? call.caller : null;
        var receiver = call && call.receiver ? call.receiver : null;
        var callerId = caller && (caller.id || caller.user_id || caller.member_id) ? parseInt(caller.id || caller.user_id || caller.member_id, 10) : null;
        var receiverId = receiver && (receiver.id || receiver.user_id || receiver.member_id) ? parseInt(receiver.id || receiver.user_id || receiver.member_id, 10) : null;

        if (callerId && userId && callerId === userId) {
            return receiver || { name: call.receiver_name || call.to_name || '', photo: call.receiver_photo || call.to_photo || '' };
        }
        if (receiverId && userId && receiverId === userId) {
            return caller || { name: call.caller_name || call.from_name || '', photo: call.caller_photo || call.from_photo || '' };
        }

        return receiver || caller || { name: call.receiver_name || call.caller_name || '', photo: call.receiver_photo || call.caller_photo || '' };
    }

    function stopCallTone() {
        if (window.currentCallToneState && window.currentCallToneState.interval) {
            clearInterval(window.currentCallToneState.interval);
        }
        if (window.currentCallToneState && window.currentCallToneState.timers) {
            window.currentCallToneState.timers.forEach(function (timeoutId) { clearTimeout(timeoutId); });
        }
        if (window.currentCallToneState && window.currentCallToneState.context && window.currentCallToneState.context.close) {
            try { window.currentCallToneState.context.close(); } catch (e) {}
        }
        window.currentCallToneState = null;
    }

    window.playCallTone = function (kind) {
        stopCallTone();
        var AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) { return; }
        try {
            var context = new AudioContextClass();
            if (context.state === 'suspended' && context.resume) {
                context.resume().catch(function () {});
            }
            var tones = kind === 'incoming' ? [784, 988, 784] : [523, 659, 523, 659];
            var state = { context: context, timers: [], interval: null };
            function playSequence() {
                var gain = context.createGain();
                gain.gain.value = 0.03;
                gain.connect(context.destination);
                tones.forEach(function (frequency, index) {
                    var timeoutId = setTimeout(function () {
                        var osc = context.createOscillator();
                        osc.type = 'sine';
                        osc.frequency.value = frequency;
                        osc.connect(gain);
                        osc.start();
                        setTimeout(function () {
                            try { osc.stop(); } catch (e) {}
                            try { osc.disconnect(); } catch (e) {}
                        }, 190);
                    }, index * 230);
                    state.timers.push(timeoutId);
                });
            }
            playSequence();
            state.interval = setInterval(function () {
                playSequence();
            }, kind === 'incoming' ? 3200 : 3600);
            window.currentCallToneState = state;
        } catch (e) {
            console.warn('[' + (window.__hamqadamGlobalCallUiPatched ? 'global' : 'chat') + ' call] tone play failed', e);
        }
    };

    function updateMicIcon() {
        $('#global-call-toggle-mic i').attr('class', window.currentCallMicMuted ? 'las la-microphone-slash' : 'las la-microphone');
    }

    function updateCameraIcon() {
        $('#global-call-toggle-camera i').attr('class', window.currentCallCameraMuted ? 'las la-video-slash' : 'las la-video');
    }

    window.showOutgoingCallModal = function (call) {
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#global-call-action-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        $('#global-call-shell-avatar').attr('src', peer.photo ? peer.photo : avatarFallback());
        $('#global-call-shell-name').text(peer.name ? peer.name : 'Calling');
        $('#global-call-shell-status').text('Calling...');
        $('#global-call-shell-meta').html('<span class="badge badge-light">' + ((call && call.call_type) || 'audio') + '</span>');
        $('#global-call-shell-accept').addClass('d-none').attr('data-call-id', '');
        $('#global-call-shell-decline').addClass('d-none').attr('data-call-id', '');
        $('#global-call-shell-cancel').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
        stopCallTone();
        window.playCallTone('outgoing');
        $('#global-call-action-modal').modal('show');
    };

    window.showIncomingCallModal = function (call) {
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#global-call-action-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        $('#global-call-shell-avatar').attr('src', peer.photo ? peer.photo : avatarFallback());
        $('#global-call-shell-name').text(peer.name ? peer.name : 'Incoming Call');
        $('#global-call-shell-status').text(call && call.call_type === 'video' ? 'Incoming Video Call' : 'Incoming Audio Call');
        $('#global-call-shell-meta').html('<div class="global-call-ringing-pill">Ringing</div>');
        $('#global-call-shell-accept').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
        $('#global-call-shell-decline').removeClass('d-none').attr('data-call-id', window.currentCallId || '');
        $('#global-call-shell-cancel').addClass('d-none').attr('data-call-id', '');
        stopCallTone();
        window.playCallTone('incoming');
        $('#global-call-action-modal').modal('show');
    };

    window.openActiveCallScreen = function (call) {
        stopCallTone();
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#global-call-screen-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        $('#global-call-screen-name').text(peer.name ? peer.name : 'Call');
        $('#global-call-screen-status').text('Connected');
        $('#global-call-screen-timer').text('00:00');
        if (call && call.call_type === 'video') {
            $('#global-call-video-area').removeClass('d-none').html('<div id="global-remote-video-area" class="global-remote-video-screen"></div><div id="global-local-video-preview" class="global-local-video-preview"></div>');
            $('#global-call-audio-area').addClass('d-none');
            $('#global-call-toggle-camera, #global-call-switch-camera').removeClass('d-none');
        } else {
            $('#global-call-video-area').addClass('d-none').empty();
            $('#global-call-audio-area').removeClass('d-none').html('<div class="global-call-audio-avatar mb-3"><img src="' + (peer.photo ? peer.photo : avatarFallback()) + '" alt="avatar"></div><h3 class="mb-0">' + (peer.name ? peer.name : '') + '</h3><p class="mb-0 text-white-50">Connected</p>');
            $('#global-call-toggle-camera, #global-call-switch-camera').addClass('d-none');
        }
        $('#global-call-screen-modal').modal('show');
        if (typeof window.startCallTimer === 'function') {
            window.startCallTimer();
        }
    };

    window.joinAgoraCall = async function (call, rtc, isCaller) {
        if (typeof AgoraRTC === 'undefined' || !rtc || !rtc.token) {
            if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.notify === 'function') {
                AIZ.plugins.notify('danger', 'Calling service unavailable.');
            }
            return;
        }
        if (window.currentCallClient || (window.currentCallTracks && window.currentCallTracks.length)) {
            window.stopCallSession(false);
        }
        window.currentCallClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
        window.currentCallClient.on('user-published', async function (user, mediaType) {
            await window.currentCallClient.subscribe(user, mediaType);
            if (mediaType === 'video') {
                $('#global-call-video-area').removeClass('d-none').html('<div id="global-remote-video-area" class="global-remote-video-screen"></div><div id="global-local-video-preview" class="global-local-video-preview"></div>');
                if (user.videoTrack) { user.videoTrack.play('global-remote-video-area'); }
            }
            if (mediaType === 'audio' && user.audioTrack) {
                user.audioTrack.play();
            }
        });
        window.currentCallClient.on('user-left', function () {
            if (typeof checkUnreadChats === 'function') { checkUnreadChats(); }
        });
        try {
            var tracks = call.call_type === 'video'
                ? await AgoraRTC.createMicrophoneAndCameraTracks()
                : [await AgoraRTC.createMicrophoneAudioTrack()];
            window.currentCallTracks = tracks;
            window.currentCallAudioTrack = tracks[0] || null;
            window.currentCallVideoTrack = call.call_type === 'video' ? (tracks[1] || null) : null;
            window.currentCallMicMuted = false;
            window.currentCallCameraMuted = false;
            updateMicIcon();
            updateCameraIcon();
            if (call.call_type === 'video' && tracks[1]) {
                $('#global-call-video-area').removeClass('d-none').html('<div id="global-remote-video-area" class="global-remote-video-screen"></div><div id="global-local-video-preview" class="global-local-video-preview"></div>');
                tracks[1].play('global-local-video-preview');
            }
            await window.currentCallClient.join(rtc.app_id, rtc.channel, rtc.token, rtc.uid);
            await window.currentCallClient.publish(tracks);
            $.ajax({
                url: '{{ route('chat.call.connect', ['call' => '__CALL__']) }}'.replace('__CALL__', call.id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') }
            });
            if (typeof window.startCallTimer === 'function') {
                window.startCallTimer();
            }
        } catch (error) {
            console.error('[global call] join failed', error);
            if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.notify === 'function') {
                AIZ.plugins.notify('danger', 'Unable to access microphone or camera');
            }
            if (typeof window.endActiveCall === 'function') {
                window.endActiveCall('failed');
            }
        }
    };

    window.stopCallSession = function (closeModal) {
        stopCallTone();
        clearTimeout(window.currentCallTimer);
        clearInterval(window.currentCallDurationTimer);
        window.currentCallTimer = null;
        window.currentCallDurationTimer = null;
        window.currentCallStartedAt = null;
        if (window.currentCallTracks && window.currentCallTracks.length) {
            window.currentCallTracks.forEach(function (track) {
                if (track && track.close) { track.close(); }
            });
        }
        window.currentCallTracks = [];
        window.currentCallAudioTrack = null;
        window.currentCallVideoTrack = null;
        window.currentCallMicMuted = false;
        window.currentCallCameraMuted = false;
        updateMicIcon();
        updateCameraIcon();
        if (window.currentCallClient) {
            window.currentCallClient.leave().catch(function () {});
        }
        window.currentCallClient = null;
        if (closeModal !== false) {
            $('#global-call-screen-modal').modal('hide');
            $('#global-call-action-modal').modal('hide');
        }
    };

    window.toggleCallMic = function () {
        var track = window.currentCallAudioTrack || (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'audio'; });
        if (!track || !track.setEnabled) { return; }
        window.currentCallMicMuted = !window.currentCallMicMuted;
        track.setEnabled(!window.currentCallMicMuted);
        updateMicIcon();
    };

    window.toggleCallCamera = function () {
        var track = window.currentCallVideoTrack || (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'video'; });
        if (!track || !track.setEnabled) { return; }
        window.currentCallCameraMuted = !window.currentCallCameraMuted;
        track.setEnabled(!window.currentCallCameraMuted);
        updateCameraIcon();
    };

    window.switchCallCamera = function () {
        var track = window.currentCallVideoTrack || (window.currentCallTracks || []).find(function (item) { return item && item.trackMediaType === 'video'; });
        if (!track || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return; }
        navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var cameras = devices.filter(function (device) { return device.kind === 'videoinput'; });
            if (cameras.length > 1 && track.setDevice) {
                track.setDevice(cameras[1].deviceId);
            }
        });
    };
})(window, window.jQuery);
</script>
    <style>
        #global-call-action-modal .modal-dialog {
            max-width: 420px;
            width: calc(100vw - 32px);
        }
        #global-call-action-modal .modal-content {
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(255, 47, 132, 0.22);
        }
        #global-call-action-modal .global-call-shell {
            padding: 28px 24px !important;
        }
        #global-call-shell-name,
        #call-shell-name {
            font-size: 22px;
            line-height: 1.2;
        }
        #global-call-shell-status,
        #call-shell-status {
            font-size: 14px;
        }
        #global-call-action-modal .btn-lg,
        #call-action-modal .btn-lg {
            min-width: 126px;
            border-radius: 16px;
        }
        #global-call-action-modal .d-flex.flex-wrap,
        #call-action-modal .d-flex.flex-wrap {
            gap: 14px !important;
        }
        #global-call-action-modal .global-call-shell-avatar,
        #call-action-modal .call-shell-avatar {
            width: 104px;
            height: 104px;
        }
        #global-call-screen-modal .modal-dialog {
            max-width: 920px;
            width: calc(100vw - 20px);
        }
    </style>
</body>

</html>
