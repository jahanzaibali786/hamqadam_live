@extends('frontend.layouts.app')

@section('content')
    <div class="py-4 py-lg-5">
        <div class="container">
            <div class="row">
                <div class="col-xxl-4 col-xl-5 col-md-7 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-5 text-center">
                                <h1 class="h3 text-primary mb-0">{{ translate('Login to your account') }}</h1>
                            </div>

                            <form id="myForm" class="" method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <label class="form-label" for="email">
                                            {{ addon_activation('otp_system') ? translate('Email/Phone') : translate('Email') }}
                                        </label>
                                        @if (addon_activation('otp_system'))
                                            <button class="btn btn-link p-0 opacity-50 text-reset fs-12" type="button"
                                                onclick="toggleEmailPhone(this)">
                                                {{ translate('Use Email Instead') }}
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Phone Input -->
                                    @if (addon_activation('otp_system'))
                                        <input type="hidden" name="email" id="finalInput" value="">

                                        <div class="form-group phone-form-group mb-1">
                                            <input type="tel" id="phone-code"
                                                class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                                value="{{ old('email') }}" placeholder="{{ translate('Phone') }}"
                                                autocomplete="off">
                                            @if ($errors->has('email'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Email Input (hidden by default when OTP enabled) -->
                                        <div class="form-group email-form-group mb-1 d-none">
                                            <input type="email" id="email"
                                                class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                                value="{{ old('email') }}" placeholder="{{ translate('Email') }}"
                                                autocomplete="off">
                                            @if ($errors->has('email'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <!-- Show only Email Input if addon is OFF -->
                                        <div class="form-group email-form-group mb-1">
                                            <input type="email" name="email" id="email"
                                                class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                                value="{{ old('email') }}" placeholder="{{ translate('Email') }}"
                                                autocomplete="off">
                                            @if ($errors->has('email'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                </div>


                                <div class="form-group">
                                    <label class="form-label" for="password">{{ translate('Password') }}</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        name="password" id="password" placeholder="********" required>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3 text-right">
                                    <a class="link-muted text-capitalize font-weight-normal"
                                        href="{{ route('password.request') }}">{{ translate('Forgot Password?') }}</a>
                                </div>

                                <div class="mb-5">
                                    <button type="submit"
                                        class="btn btn-block btn-primary">{{ translate('Login to your Account') }}</button>
                                </div>
                            </form>
                            @if (env('DEMO_MODE') == 'On')
                                <div class="mb-5">
                                    <table class="table table-bordered table-responsive">
                                        <tbody>
                                            <tr>
                                                <td>user2@example.com</td>
                                                <td>12345678</td>
                                                <td class="text-center">
                                                    <button class="btn btn-outline-primary btn-xs"
                                                        onclick="autoFill1()">{{ translate('Copy credentials') }}</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>user17@example.com</td>
                                                <td>12345678</td>
                                                <td class="text-center">
                                                    <button class="btn btn-outline-primary btn-xs"
                                                        onclick="autoFill2()">{{ translate('Copy credentials') }}</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if (get_setting('google_login_activation') == 1 ||
                                    get_setting('facebook_login_activation') == 1 ||
                                    get_setting('twitter_login_activation') == 1 ||
                                    get_setting('apple_login_activation') == 1)
                                <div class="separator mb-3">
                                    <span class="bg-white px-3 opacity-60">{{ translate('Or Login With') }}</span>
                                </div>
                                <ul class="list-inline social colored text-center mb-5">
                                    @if (get_setting('facebook_login_activation') == 1)
                                        <li class="list-inline-item">
                                            <a href="{{ route('social.login', ['provider' => 'facebook']) }}"
                                                class="facebook">
                                                <i class="lab la-facebook-f"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (get_setting('google_login_activation') == 1)
                                        <li class="list-inline-item">
                                            <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google">
                                                <i class="lab la-google"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (get_setting('twitter_login_activation') == 1)
                                        <li class="list-inline-item">
                                            <a href="{{ route('social.login', ['provider' => 'twitter']) }}"
                                                class="twitter">
                                                <i class="lab la-twitter"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (get_setting('apple_login_activation') == 1)
                                        <li class="list-inline-item">
                                            <a href="{{ route('social.login', ['provider' => 'apple']) }}" class="apple">
                                                <i class="lab la-apple"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            @endif
                            <div class="text-center">
                                <p class="text-muted mb-0">{{ translate("Don't have an account?") }}</p>
                                <a href="{{ route('register') }}">{{ translate('Create an account') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        var isOTPActive = {{ addon_activation('otp_system') ? 'true' : 'false' }};
        document.getElementById("myForm").addEventListener("submit", function(event) {
            if (isOTPActive) {
                if ($(".phone-form-group").hasClass("d-none")) {
                    const emailInput = $("#email").val();
                    $("#finalInput").val(emailInput);
                } else {
                    event.preventDefault();
                    let fullNumber = iti.getNumber(); 
                    $("#finalInput").val(fullNumber);
                }
            }
            this.submit();
        });
    </script>
    @if (addon_activation('otp_system'))
        @include('partials.emailOrPhone')
    @endif
@endsection
