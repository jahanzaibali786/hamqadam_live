@extends('admin.layouts.blank')

@section('content')
<style>
.hq-admin-login { position:relative; min-height:100vh; overflow:hidden; background:#fcecf0 url('{{ static_asset('assets/img/hamqadam-luxury/hero-hearts.png') }}') center / cover no-repeat; }
.hq-admin-login::before { content:''; position:absolute; inset:0; background:linear-gradient(90deg,rgba(255,248,250,.95),rgba(255,248,250,.70)); }
.hq-admin-login > .container { position:relative; z-index:1; }
.hq-admin-login .card { overflow:hidden; border:1px solid rgba(255,255,255,.95); border-radius:24px; background:rgba(255,250,251,.92); box-shadow:0 28px 80px rgba(72,4,24,.22); backdrop-filter:blur(12px); }
.hq-admin-login .card-body { padding:42px; }
.hq-admin-login h1 { color:#67051f !important; font-family:Cormorant Garamond,Georgia,serif; font-size:42px; }
.hq-admin-login .form-control { min-height:52px; border:1px solid #d7a6b7; border-radius:9px; background:rgba(255,255,255,.96); }
.hq-admin-login .form-control:focus { border-color:#d75f87; box-shadow:0 0 0 3px rgba(215,95,135,.13); }
.hq-admin-login .btn-primary { min-height:52px; border:0; border-radius:999px; background:linear-gradient(100deg,#ee91ae,#c94c78); box-shadow:0 10px 24px rgba(201,76,120,.22); }
</style>

<div class="hq-admin-login h-100 bg-cover bg-center py-5 d-flex align-items-center">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-xl-5 col-xxl-4 mx-auto">
                <div class="card hq-admin-login-card text-left">
                    <div class="card-body">
                        <div class="mb-5 text-center">
                            <img src="{{ uploaded_asset(get_setting('system_logo_black')) }}" class="mw-100 mb-4" height="40">
                            <h1 class="h3 text-primary mb-0">{{ translate('Welcome to') }} {{ env('APP_NAME') }}</h1>
                            <p>{{ translate('Login to your account.') }}</p>
                        </div>
                        <form class="pad-hor" method="POST" role="form" action="{{ route('login') }}">
                            @csrf
                            <div class="form-group">
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ translate('Email') }}">
                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required placeholder="{{ translate('Password') }}">
                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6">
                                    <div class="text-left">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <span>{{ translate('Remember Me') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                @if(env('MAIL_USERNAME') != null && env('MAIL_PASSWORD') != null)
                                    <div class="col-sm-6">
                                        <div class="text-right">
                                            <a href="{{ route('password.request') }}" class="text-reset fs-14">{{translate('Forgot password ?')}}</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                {{ translate('Login') }}
                            </button>
                        </form>
                        @if (env("DEMO_MODE") == "On")
                            <div class="mt-4">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td>admin@example.com</td>
                                            <td>12345678</td>
                                            <td><button class="btn btn-primary btn-xs" onclick="autoFill()">{{ translate('Copy') }}</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
	<script>
        function autoFill(){
            $('#email').val('admin@example.com');
            $('#password').val('12345678');
        }
    </script>
@endsection
