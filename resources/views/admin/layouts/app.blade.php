<!doctype html>
@if (\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
    <html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@endif

<head>
    <!-- Required meta tags -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>{{ get_setting('website_name') . ' | ' . get_setting('site_motto') }}</title>

    <!-- Favicon -->
    <link name="favicon" type="image/x-icon" href="{{ uploaded_asset(get_setting('site_icon')) }}"
        rel="shortcut icon" />

    <!-- google font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">

    <!-- vendors css -->
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">

    <!-- aiz core css -->
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css?v=') }}{{ rand(1000,9999) }}">

    @if (\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
        <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif


    <script>
        var AIZ = AIZ || {};
    </script>

</head>

<body>

    <div class="aiz-main-wrapper">

        @include('admin.inc.sidenav')

        <div class="aiz-content-wrapper">

            @include('admin.inc.header')

            <!-- Main Content start-->
            <div class="aiz-main-content">
                <div class="px-15px px-lg-25px">
                    @yield('content')
                </div>

                <!-- Footer -->
                <div class="bg-white text-center py-3 px-15px px-lg-25px mt-auto">
                    <p class="mb-0">&copy; {{ env('APP_NAME') }}</p>
                </div>
            </div>
            <!-- Mian content end -->

        </div>

    </div>

    @yield('modal')

    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.js') }}"></script>

    @php
        $firebaseAdminConfig = [
            'apiKey' => (string) env('FCM_API_KEY'),
            'authDomain' => (string) env('FCM_AUTH_DOMAIN'),
            'projectId' => (string) env('FCM_PROJECT_ID'),
            'storageBucket' => (string) env('FCM_STORAGE_BUCKET'),
            'messagingSenderId' => (string) env('FCM_MESSAGING_SENDER_ID'),
            'appId' => (string) env('FCM_APP_ID'),
        ];
        $firebaseAdminReady = get_setting('firebase_push_notification') == 1
            && collect($firebaseAdminConfig)->every(fn ($value) => trim($value) !== '');
    @endphp
    @if ($firebaseAdminReady)
        <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js"></script>
        <script>
            (function () {
                var firebaseConfig = @json($firebaseAdminConfig);
                if (!window.firebase || !firebaseConfig.projectId) { return; }
                if (!firebase.apps.length) { firebase.initializeApp(firebaseConfig); }
                var messaging = firebase.messaging();

                function initFirebaseMessagingRegistration() {
                    if (!('Notification' in window)) { return; }
                    messaging.requestPermission()
                        .then(function () { return messaging.getToken(); })
                        .then(function (token) {
                            if (!token) { return; }
                            $.ajax({
                                url: @json(route('fcmToken')),
                                type: 'POST',
                                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                                data: { fcm_token: token },
                                dataType: 'JSON'
                            });
                        })
                        .catch(function () {});
                }

                var serviceWorkerUrl = @json(rtrim(getBaseURL(), '/') . '/firebase-messaging-sw.js');
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register(serviceWorkerUrl)
                        .then(function (registration) {
                            if (typeof messaging.useServiceWorker === 'function') {
                                messaging.useServiceWorker(registration);
                            }
                            initFirebaseMessagingRegistration();
                        })
                        .catch(function () {});
                }
                messaging.onMessage(function (payload) {
                    var data = payload && payload.data ? payload.data : {};
                    if (Notification.permission === 'granted') {
                        new Notification(data.title || 'Hamqadam', { body: data.body || '' });
                    }
                });
            }());
        </script>
    @endif

    @yield('script')



    <script type="text/javascript">
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify(@json($message['level']), @json($message['message']));
        @endforeach

        // language Switch
        if ($('#lang-change').length > 0) {
            $('#lang-change .dropdown-menu a').each(function() {
                $(this).on('click', function(e) {
                    e.preventDefault();
                    var $this = $(this);
                    var locale = $this.data('flag');
                    $.post('{{ route('language.change') }}', {
                        _token: '{{ csrf_token() }}',
                        locale: locale
                    }, function(data) {
                        location.reload();
                    });

                });
            });
        }
        function menuSearch(){
			var filter, item;
			filter = $("#menu-search").val().toUpperCase();
			items = $("#main-menu").find("a");
			items = items.filter(function(i,item){
				if($(item).find(".aiz-side-nav-text")[0].innerText.toUpperCase().indexOf(filter) > -1 && $(item).attr('href') !== '#'){
					return item;
				}
			});

			if(filter !== ''){
				$("#main-menu").addClass('d-none');
				$("#search-menu").html('')
				if(items.length > 0){
					for (i = 0; i < items.length; i++) {
						const text = $(items[i]).find(".aiz-side-nav-text")[0].innerText;
						const link = $(items[i]).attr('href');
						 $("#search-menu").append(`<li class="aiz-side-nav-item"><a href="${link}" class="aiz-side-nav-link"><i class="las la-ellipsis-h aiz-side-nav-icon"></i><span>${text}</span></a></li`);
					}
				}else{
					$("#search-menu").html(`<li class="aiz-side-nav-item"><span	class="text-center text-muted d-block">{{ translate('Nothing Found') }}</span></li>`);
				}
			}else{
				$("#main-menu").removeClass('d-none');
				$("#search-menu").html('')
			}
        }
    </script>


</body>

</html>
